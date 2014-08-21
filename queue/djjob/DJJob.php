<?php

# This system is mostly a port of delayed_job: http://github.com/tobi/delayed_job

class DJException extends Exception { }

class DJRetryException extends DJException {

    private $delay_seconds = 7200;

    public function setDelay($delay) {
        $this->delay_seconds = $delay;
    }
    public function getDelay() {
        return $this->delay_seconds;
    }
}

class DJBase {

    const MAX_ERRORS = 5;
    const ALERT_MAIL = 'tecnico@theetailers.com';
    // error severity levels
    const CRITICAL = 4;
    const    ERROR = 3;
    const     WARN = 2;
    const     INFO = 1;
    const    DEBUG = 0;

    private static $log_level = self::DEBUG;

    private static $db = null;

    private static $dsn = "";
    private static $options = array(
      "mysql_user" => null,
      "mysql_pass" => null,
    );

    // use either `configure` or `setConnection`, depending on if
    // you already have a PDO object you can re-use
    public static function configure($dsn, $options = array()) {
        self::$dsn = $dsn;
        self::$options = array_merge(self::$options, $options);
    }

    public static function setLogLevel($const) {
        self::$log_level = $const;
    }
    public static function resetConnection() {
        self::$db = null;
    }
    public static function setConnection(PDO $db) {
        self::$db = $db;
    }

    protected static function getConnection() {
        if (self::$db === null) {
            if (!self::$dsn) {
                throw new DJException("Please tell DJJob how to connect to your database by calling DJJob::configure(\$dsn, [\$options = array()]) or re-using an existing PDO connection by calling DJJob::setConnection(\$pdoObject). If you're using MySQL you'll need to pass the db credentials as separate 'mysql_user' and 'mysql_pass' options. This is a PDO limitation, see [http://stackoverflow.com/questions/237367/why-is-php-pdo-dsn-a-different-format-for-mysql-versus-postgresql] for an explanation.");
            }
            try {
                // http://stackoverflow.com/questions/237367/why-is-php-pdo-dsn-a-different-format-for-mysql-versus-postgresql
                if (self::$options["mysql_user"] !== null) {
                    self::$db = new PDO(self::$dsn, self::$options["mysql_user"], self::$options["mysql_pass"]);
                } else {
                    self::$db = new PDO(self::$dsn);
                }
                self::$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            } catch (PDOException $e) {
                throw new Exception("DJJob couldn't connect to the database. PDO said [{$e->getMessage()}]");
            }
        }
        return self::$db;
    }

    public static function runQuery($sql, $params = array()) {
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute($params);

        $ret = array();
        if ($stmt->rowCount()) {
            // calling fetchAll on a result set with no rows throws a
            // "general error" exception
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
                $ret []= $r;
            }
        }

        $stmt->closeCursor();
        return $ret;
    }

    public static function runUpdate($sql, $params = array()) {
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    protected static function log($mesg, $severity=self::CRITICAL) {
        if ($severity >= self::$log_level) {
            printf("[%s] %s\n", date('c'), $mesg);
        }
    }
}

class DJWorker extends DJBase {
    # This is a singleton-ish thing. It wouldn't really make sense to
    # instantiate more than one in a single request (or commandline task)
    protected $env;
    protected $name;

    public function __construct($options = array(), $env) {
        $options = array_merge(array(
            "queue" => "default",
            "count" => 0,
            "sleep" => 5,
            "max_attempts" => 5
        ), $options);
        list($this->queue, $this->count, $this->sleep, $this->max_attempts) =
            array($options["queue"], $options["count"], $options["sleep"], $options["max_attempts"]);

        list($hostname, $pid) = array(trim(`hostname`), getmypid());
        $this->name = "host::$hostname pid::$pid";

        if (function_exists("pcntl_signal")) {
            pcntl_signal(SIGTERM, array($this, "handleSignal"));
            pcntl_signal(SIGINT, array($this, "handleSignal"));
        }
        $this->env = $env;
    }

    public function handleSignal($signo) {
        $signals = array(
            SIGTERM => "SIGTERM",
            SIGINT  => "SIGINT"
        );
        $signal = $signals[$signo];

        $this->log("[WORKER] Received received {$signal}... Shutting down", self::INFO);
        $this->releaseLocks();
        die(0);
    }

    public function releaseLocks() {
        $this->runUpdate("
            UPDATE jobs
            SET locked_at = NULL, locked_by = NULL
            WHERE locked_by = ?",
            array($this->name)
        );
    }

    /**
     * Returns a new job ordered by most recent first
     * why this?
     *     run newest first, some jobs get left behind
     *     run oldest first, all jobs get left behind
     * @return DJJob
     */
    public function getNewJob() {
        # we can grab a locked job if we own the lock
        $rs = $this->runQuery("
            SELECT id
            FROM   jobs
            WHERE  queue = ?
            AND    (run_at IS NULL OR NOW() >= run_at)
            AND    (locked_at IS NULL OR locked_by = ?)
            AND    failed_at IS NULL
            AND    attempts < ?
            ORDER BY created_at DESC
            LIMIT  10
        ", array($this->queue, $this->name, $this->max_attempts));

        // randomly order the 10 to prevent lock contention among workers
        shuffle($rs);

        foreach ($rs as $r) {
            $job = new DJJob($this->name, $r["id"], array(
                "max_attempts" => $this->max_attempts
            ), $this->env);
            if ($job->acquireLock()) return $job;
        }

        return false;
    }

    /**
     * start
     * Wait until the maximun number or jobs to process is reached,
     * or indefinedly if no maximun is set.
     *
     * @access public
     * @return void
     */
    public function start() {
        $this->log("[JOB] Starting worker {$this->name} on queue::{$this->queue}", self::INFO);
        $errors = array();
        $count = 0;
        $job_count = 0;
        while ($this->count == 0 || $count < $this->count) {
            try {
                if (function_exists("pcntl_signal_dispatch")) pcntl_signal_dispatch();

                $count += 1;
                $job = $this->getNewJob($this->queue);

                if (!$job) {
                    $this->log("[JOB] Failed to get a job, queue::{$this->queue} may be empty", self::DEBUG);
                    sleep($this->sleep);
                    continue;
                }

                $job_count += 1;
                $job->run();
            } catch (Exception $e) {
                $this->log("[JOB] unhandled exception::\"{$e->getMessage()}\"", self::ERROR);
                $errors[] = date('y-m-d');
                //Stop queue, and send an alert email if several errors occur on the same day
                if(count(array_keys($errors, date('y-m-d'))) > self::MAX_ERRORS) {
                    $message = 'The following error raised an exception: ' . "\n" . $e->getMessage() . "\n" . 'Summary: ' . "\n" .
                        "Job worker shutting down after running {$job_count} jobs, over {$count} polling iterations";
                    mail(self::ALERT_MAIL, '[' . $this->env . '][ERROR] Processing Queue Failed', $message);
                    $this->log("[JOB] Sending alert email");
                    break;
                }
                $errors = array_filter($errors, function($val) {
                    return $val === date('y-m-d');
                });
                //Retry after a while, reseting the db connection
                sleep($this->sleep);
                DJJob::resetConnection();
            }

        }
        $this->log("[JOB] worker shutting down after running {$job_count} jobs, over {$count} polling iterations", self::INFO);
    }
}

class DJJob extends DJBase {

    public function __construct($worker_name, $job_id, $options = array(), $env) {
        $options = array_merge(array(
            "max_attempts" => 5
        ), $options);
        $this->worker_name = $worker_name;
        $this->job_id = $job_id;
        $this->max_attempts = $options["max_attempts"];
        $this->env = $env;
    }

    public function run() {
        # pull the handler from the db
        $handler = $this->getHandler();
        if (!is_object($handler)) {
            $this->log("[JOB] bad handler for job::{$this->job_id}", self::ERROR);
            $this->finishWithError("bad handler for job::{$this->job_id}");
            mail(self::ALERT_MAIL, '[' . $this->env . '][ERROR] Processing Job failed', "bad handler for job::{$this->job_id}" . "\n" . var_export($handler, true));
            //$this->finish();
            return false;
        }

        # run the handler
        try {
            $handler->perform($this->job_id);

            # cleanup
            $this->finish();
            return true;

        } catch (DJRetryException $e) {
            # attempts hasn't been incremented yet.
            $attempts = $this->getAttempts()+1;

            $msg = "Caught DJRetryException \"{$e->getMessage()}\" on attempt $attempts/{$this->max_attempts}.";

            if($attempts == $this->max_attempts) {
                $this->log("[JOB] job::{$this->job_id} $msg Giving up.");
                $this->finishWithError($msg, $handler);
                //Send alert email
                mail(self::ALERT_MAIL, '[ERROR] Processing Job failed', $msg . "\n" . var_export($handler, true));
                $this->finish();

            } else {
                $this->log("[JOB] job::{$this->job_id} $msg Try again in {$e->getDelay()} seconds.", self::WARN);
                $this->retryLater($e->getDelay());
            }
            return false;

        } catch (Exception $e) {

            $this->finishWithError($e->getMessage(), $handler);
            return false;

        }
    }

    public function acquireLock() {
        $this->log("[JOB] attempting to acquire lock for job::{$this->job_id} on {$this->worker_name}", self::INFO);

        $lock = $this->runUpdate("
            UPDATE jobs
            SET    locked_at = NOW(), locked_by = ?
            WHERE  id = ? AND (locked_at IS NULL OR locked_by = ?) AND failed_at IS NULL
        ", array($this->worker_name, $this->job_id, $this->worker_name));

        if (!$lock) {
            $this->log("[JOB] failed to acquire lock for job::{$this->job_id}", self::INFO);
            return false;
        }

        return true;
    }

    public function releaseLock() {
        $this->runUpdate("
            UPDATE jobs
            SET locked_at = NULL, locked_by = NULL
            WHERE id = ?",
            array($this->job_id)
        );
    }

    public function finish() {
        $this->runUpdate(
            "DELETE FROM jobs WHERE id = ?",
            array($this->job_id)
        );
        $this->log("[JOB] completed job::{$this->job_id}", self::INFO);
    }

    public function finishWithError($error, $handler = null) {
        $this->runUpdate("
            UPDATE jobs
            SET attempts = attempts + 1,
                failed_at = IF(attempts >= ?, NOW(), NULL),
                error = IF(attempts >= ?, ?, NULL)
            WHERE id = ?",
            array(
                $this->max_attempts,
                $this->max_attempts,
                $error,
                $this->job_id
            )
        );
        $this->log("[JOB] failure in job::{$this->job_id}", self::ERROR);
        $this->releaseLock();

        if ($handler && ($this->getAttempts() == $this->max_attempts) && method_exists($handler, '_onDjjobRetryError')) {
          $handler->_onDjjobRetryError($error);
        }
    }

    public function retryLater($delay) {
        $this->runUpdate("
            UPDATE jobs
            SET run_at = DATE_ADD(NOW(), INTERVAL ? SECOND),
                attempts = attempts + 1
            WHERE id = ?",
            array(
              $delay,
              $this->job_id
            )
        );
        $this->releaseLock();
    }

    public function getHandler() {
        $rs = $this->runQuery(
            "SELECT handler FROM jobs WHERE id = ?",
            array($this->job_id)
        );
        foreach ($rs as $r) return unserialize($r["handler"]);
        return false;
    }

    public function getAttempts() {
        $rs = $this->runQuery(
            "SELECT attempts FROM jobs WHERE id = ?",
            array($this->job_id)
        );
        foreach ($rs as $r) return $r["attempts"];
        return false;
    }

    public static function enqueue($handler, $project = NULL, $queue = "default", $run_at = null) {
        //Avoid reindexing the same project if already in queue
        if($project && get_class($handler) == 'ReindexJob' && in_array($project, self::getProjects($queue))) {
            return true;
        }
        $affected = self::runUpdate(
            "INSERT INTO jobs (handler, queue, project, run_at, created_at) VALUES(?, ?, ?, ?, NOW())",
            array(serialize($handler), (string) $queue, $project, $run_at)
        );

        if ($affected < 1) {
            self::log("[JOB] failed to enqueue new job", self::ERROR);
            return false;
        }

        return true;
    }

    public static function bulkEnqueue($handlers, $project = NULL, $queue = "default", $run_at = null) {
        $sql = "INSERT INTO jobs (handler, queue, project, run_at, created_at) VALUES";
        $sql .= implode(",", array_fill(0, count($handlers), "(?, ?, ?, ?, NOW())"));

        $parameters = array();
        foreach ($handlers as $handler) {
            $parameters []= serialize($handler);
            $parameters []= (string) $queue;
            $parameters []= (string) $project;
            $parameters []= $run_at;
        }
        $affected = self::runUpdate($sql, $parameters);

        if ($affected < 1) {
            self::log("[JOB] failed to enqueue new jobs", self::ERROR);
            return false;
        }

        if ($affected != count($handlers))
            self::log("[JOB] failed to enqueue some new jobs", self::ERROR);

        return true;
    }

    public static function status($queue = "default") {
        $rs = self::runQuery("
            SELECT COUNT(*) as total, COUNT(failed_at) as failed, COUNT(locked_at) as locked
            FROM `jobs`
            WHERE queue = ?
        ", array($queue));
        $rs = $rs[0];

        $failed = $rs["failed"];
        $locked = $rs["locked"];
        $total  = $rs["total"];
        $outstanding = $total - $locked - $failed;

        return array(
            "outstanding" => $outstanding,
            "locked" => $locked,
            "failed" => $failed,
            "total"  => $total
        );
    }
    public static function getPercentage($project) {
        return reset(self::runQuery("
            SELECT locked_at started_at, error, percentage from jobs WHERE project = ? ORDER BY percentage desc
            ", array($project)));
    }
    public static function getStats($project) {
        return reset(self::runQuery("
            SELECT product_save, reindex  from stats WHERE project = ? ", array($project)));
    }
    public static function updateStats($project, $field, $value) {
        echo $project . " - > " . $field . " -> " . $value . "\n";
        return self::runUpdate("
            INSERT INTO stats SET {$field}  = ?, project = ?
            ON DUPLICATE KEY UPDATE {$field} = (IFNULL({$field}, {$value}) + {$value}) / 2
            ", array($value, $project));
    }
    public static function updateProgress($job_id, $instance, $percentage) {
        return self::runUpdate("
            UPDATE `jobs` set percentage = ?, handler = ?
            WHERE id = ?
            ", array($percentage, serialize($instance), $job_id));
    }
    public static function getProjects($queue = "default") {
        $rs = self::runQuery("
            SELECT project FROM `jobs`
            WHERE queue = ?
            ", array($queue));
        $projects = array();
        foreach ($rs as $project) {
            $projects[] = $project['project'];
        }
        return $projects;
    }

}
