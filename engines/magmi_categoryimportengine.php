<?php

/**
 * MAGENTO MASS IMPORTER CLASS
 *
 * version : 0.6
 * author : S.BRACQUEMONT aka dweeves
 * updated : 2010-10-09
 *
 */

/* use external file for db helper */
require_once("magmi_engine.php");

/**
 *
 * Magmi Product Import engine class
 * This class handle product import
 * @author dweeves
 *
 */
class Magmi_CategoryImportEngine extends Magmi_Engine
{

	public $attrinfo=array();
	public $attrbytype=array();
	public $store_ids=array();
	public $status_id=array();
	public $attribute_sets=array();
	public $cat_etype;
	public $sidcache=array();
	public $mode="update";
	private $_attributehandlers;
	private $_current_row;
	private $_optidcache=null;
	private $_curitemids=array("pid"=>null);
	private $_dstore=array();
	private $_same;
	private $_currentpid;
	private $_extra_attrs;
	private $_profile;
	private $_sid_wsscope=array();
	private $_sid_sscope=array();
	private $_catcols=array();
	private $_stockcols=array();
	private $_skustats=array();

/*
	public function addExtraAttribute($attr)
	{
		$attinfo=$this->attrinfo[$attr];
		$this->_extra_attrs[$attinfo["backend_type"]]["data"][]=$attinfo;

	}
	* */
	
	/**
	 * constructor
	 * @param string $conffile : configuration .ini filename
	 */
	public function __construct()
	{
		$this->setBuiltinPluginClasses("itemprocessors",dirname(dirname(__FILE__))."/plugins/inc/magmi_defaultattributehandler.php::Magmi_DefaultAttributeItemProcessor");
	}

	public function getImportMode()
	{
		return $this->mode;
	}
	
	public function initCatType()
	{
		$tname=$this->tablename("eav_entity_type");
		$this->cat_etype=$this->selectone("SELECT entity_type_id FROM $tname WHERE entity_type_code=?","catalog_category","entity_type_id");
	}

	public function getPluginFamilies()
	{
		return array("datasources","general","itemprocessors");
	}


	/**
	 *
	 * Return list of store codes that share the same website than the stores passed as parameter
	 * @param string $scodes comma separated list of store view codes
	 */
	public function getStoreIdsForWebsiteScope($scodes)
	{
		if(!isset($this->_sid_wsscope[$scodes]))
		{
			$this->_sid_wsscope[$scodes]=array();
			$wscarr=csl2arr($scodes);
			$qcolstr=$this->arr2values($wscarr);
			$cs=$this->tablename("core_store");
			$sql="SELECT csdep.store_id FROM $cs as csmain 
				 JOIN $cs as csdep ON csdep.website_id=csmain.website_id
				 WHERE csmain.code IN ($qcolstr) ";
			$sidrows=$this->selectAll($sql,$wscarr);
			foreach($sidrows as $sidrow)
			{
				$this->_sid_wsscope[$scodes][]=$sidrow["store_id"];
			}
		}
		return $this->_sid_wsscope[$scodes];
	}

	public function getStoreIdsForStoreScope($scodes)
	{
		if(!isset($this->_sid_sscope[$scodes]))
		{
			$this->_sid_sscope[$scodes]=array();
			$scarr=csl2arr($scodes);
			$qcolstr=$this->arr2values($scarr);
			$cs=$this->tablename("core_store");
			$sql="SELECT csmain.store_id from $cs as csmain WHERE csmain.code IN ($qcolstr)";
			$sidrows=$this->selectAll($sql,$scarr);
			foreach($sidrows as $sidrow)
			{
				$this->_sid_sscope[$scodes][]=$sidrow["store_id"];
			}

		}
		return $this->_sid_sscope[$scodes];
	}


	/**
	 * returns mode
	 */
	public function getMode()
	{
		return $this->mode;
	}

	public function getCatCols()
	{
		if(count($this->_catcols)==0)
		{
			$sql='DESCRIBE '.$this->tablename('catalog_category_entity');
			$rows=$this->selectAll($sql);
			foreach($rows as $row)
			{
				$this->_catcols[]=$row['Field'];
			}
		}
		return $this->_catcols;
	}

	/*
	 * Initialize attribute infos to be used during import
	 * @param array $cols : array of attribute names
	 */
	public function checkRequired($cols)
	{
		$eav_attr=$this->tablename("eav_attribute");
		$sql="SELECT attribute_code FROM $eav_attr WHERE  is_required=1
		AND frontend_input!='' AND frontend_label!='' AND entity_type_id=?";
		$required=$this->selectAll($sql,$this->cat_etype);
		$reqcols=array();
		foreach($required as $line)
		{
			$reqcols[]=$line["attribute_code"];
		}
		$required=array_diff($reqcols,$cols);
		return $required;
	}

	/**
	 *
	 * gets attribute metadata from DB and put it in attribute metadata caches
	 * @param array $cols list of attribute codes to get metadata from
	 *                    if in this list, some values are not attribute code, no metadata will be cached.
	 */
	public function initAttrInfos($cols)
	{
		if($this->cat_etype==null)
		{
			//Find product entity type
			$tname=$this->tablename("eav_entity_type");
			$this->cat_etype=$this->selectone("SELECT entity_type_id FROM $tname WHERE entity_type_code=?","catalog_category","entity_type_id");
		}

		$toscan=array_values(array_diff($cols,array_keys($this->attrinfo)));
		if(count($toscan)>0)
		{
			//create statement parameter string ?,?,?.....
			$qcolstr=$this->arr2values($toscan);

			$tname=$this->tablename("eav_attribute");
			if($this->getMagentoVersion()!="1.3.x")
			{
				$extra=$this->tablename("catalog_eav_attribute");
				//SQL for selecting attribute properties for all wanted attributes
				$sql="SELECT `$tname`.*,$extra.is_global FROM `$tname`
				LEFT JOIN $extra ON $tname.attribute_id=$extra.attribute_id
				WHERE  ($tname.attribute_code IN ($qcolstr)) AND (entity_type_id=?)";		
			}
			else
			{
				$sql="SELECT `$tname`.* FROM `$tname` WHERE ($tname.attribute_code IN ($qcolstr)) AND (entity_type_id=?)";
			}
			$toscan[]=$this->cat_etype;
			$result=$this->selectAll($sql,$toscan);

			$attrinfs=array();
			//create an attribute code based array for the wanted columns
			foreach($result as $r)
			{
				$attrinfs[$r["attribute_code"]]=$r;
			}
			unset($result);

			//create a backend_type based array for the wanted columns
			//this will greatly help for optimizing inserts when creating attributes
			//since eav_ model for attributes has one table per backend type
			foreach($attrinfs as $k=>$a)
			{
				//do not index attributes that are not in header (media_gallery may have been inserted for other purposes)
				if(!in_array($k,$cols))
				{
					continue;
				}
				$bt=$a["backend_type"];
				if(!isset($this->attrbytype[$bt]))
				{
					$this->attrbytype[$bt]=array("data"=>array());
				}
				$this->attrbytype[$bt]["data"][]=$a;
			}
			//now add a fast index in the attrbytype array to store id list in a comma separated form
			foreach($this->attrbytype as $bt=>$test)
			{
				$idlist;
				foreach($test["data"] as $it)
				{
					$idlist[]=$it["attribute_id"];
				}
				$this->attrbytype[$bt]["ids"]=implode(",",$idlist);
			}
			$this->attrinfo=array_merge($this->attrinfo,$attrinfs);
		}
		$notattribs=array_diff($cols,array_keys($this->attrinfo));
		foreach($notattribs as $k)
		{
			$this->attrinfo[$k]=null;
		}
		/*now we have 2 index arrays
		 1. $this->attrinfo  which has the following structure:
		 key : attribute_code
		 value : attribute_properties
		 2. $this->attrbytype which has the following structure:
		 key : attribute backend type
		 value : array of :
		 data => array of attribute_properties ,one for each attribute that match
		 the backend type
		 ids => list of attribute ids of the backend type */
	}

	/**
	 *
	 * retrieves attribute metadata
	 * @param string $attcode attribute code
	 * @param boolean $lookup if set, this will try to get info from DB otherwise will get from cache and may return null if not cached
	 * @return array attribute metadata info
	 */
	public function getAttrInfo($attcode,$lookup=true)
	{
		$attrinf=isset($this->attrinfo[$attcode])?$this->attrinfo[$attcode]:null;
		if($attrinf==null && $lookup)
		{
			$this->initAttrInfos(array($attcode));

		}
		if(count($this->attrinfo[$attcode])==0)
		{

			$attrinf=null;
			unset($this->attrinfo[$attcode]);
		}
		else
		{
			$attrinf=$this->attrinfo[$attcode];
		}
		return $attrinf;
	}

	/**
	 * retrieves attribute set id for a given attribute set name
	 * @param string $asname : attribute set name
	 */
	public function getAttributeSetId($asname)
	{

		if(!isset($this->attribute_sets[$asname]))
		{
			$tname=$this->tablename("eav_attribute_set");
			$asid=$this->selectone(
				"SELECT attribute_set_id FROM $tname WHERE attribute_set_name=? AND entity_type_id=?",
			array($asname,$this->cat_etype),
				'attribute_set_id');
			$this->attribute_sets[$asname]=$asid;
		}
		return $this->attribute_sets[$asname];
	}

	
        /**
	 * Retrieves category id for a category id
	 */
	public function getCategoryIds($category_id)
	{
		$tname=$this->tablename("catalog_category_entity");
		$result=$this->selectAll(
		"SELECT entity_id as pid,attribute_set_id as asid FROM $tname WHERE entity_id=?", $category_id);
		if(count($result)>0)
		{
			return $result[0];
		}
		else
		{
			return false;
		}
	}
        
	/**
	 * creates a category in magento database
	 * @param array $item: category attributes as array with key:attribute name,value:attribute value
	 * @param int $asid : attribute set id for values
	 * @return : product id for newly created product
	 */
	public function createCategory($item,$asid)
	{

		$tname=$this->tablename('catalog_category_entity');
                $item['entity_id']=$item['category_id'];
                $item['path']=$this->getPathCategory($item);
		
                if(!empty($item['path'])){
                    $item['path'].="/".$item['entity_id'];
                    
                    $item['attribute_set_id']=$asid;
                    $item['entity_type_id']=$this->cat_etype;
                    $item['created_at']=strftime("%Y-%m-%d %H:%M:%S");
                    $item['updated_at']=strftime("%Y-%m-%d %H:%M:%S");
                    $columns=array_intersect(array_keys($item), $this->getCatCols());
                    $values=$this->filterkvarr($item, $columns);
                    $sql="INSERT INTO `$tname` (".implode(",",$columns).") VALUES (".$this->arr2values($columns).")";
                    //$this->log($sql,"debug");
                    $lastid=$this->insert($sql,array_values($values));
                    return $lastid;
                }else{
                    return false;
                }
	}
	
        public function getPathCategory($item)
	{
                $tname=$this->tablename("catalog_category_entity");
                if(empty($item['parent_id'])){
                    $parentId = $this->selectone("SELECT MIN(entity_id) as entity_id FROM $tname", null, "entity_id");
                    $item['parent_id'] = $parentId;
                }
                
		$parentPath = $this->selectone("SELECT path FROM $tname WHERE entity_id=?",array($item['parent_id']),"path");
                
                return $parentPath;
	}

	/**
	 * Updateds product update time
	 * @param unknown_type $pid : entity_id of product
	 */
	public function touchProduct($pid)
	{
		$tname=$this->tablename('catalog_category_entity');
		$this->update("UPDATE $tname SET updated_at=? WHERE entity_id=?",array(strftime("%Y-%m-%d %H:%M:%S"),$pid));
	}


	/**
	 *
	 * Return affected store ids for a given item given an attribute scope
	 * @param array $item : item to get store for scope
	 * @param string $scope : scope to get stores from.
	 */
	public function getItemStoreIds($item,$scope)
	{
		switch($scope){
			//global scope
			case 1:
				$bstore_ids=$this->getStoreIdsForStoreScope("admin");
				break;
				//store scope
			case 0:
				$bstore_ids=$this->getStoreIdsForStoreScope($item["store"]);
				break;
				//website scope
			case 2:
				$bstore_ids=$this->getStoreIdsForWebsiteScope($item["store"]);
				break;
		}

		$itemstores=array_unique(array_merge($this->_dstore,$bstore_ids));
		sort($itemstores);
		return $itemstores;
	}

	/**
	 * Create category attribute from values for a given category id
	 * @param $pid : category id to create attribute values for
	 * @param $item : attribute values in an array indexed by attribute_code
	 */
	public function createAttributes($pid,&$item,$attmap,$isnew)
	{
		/**
		 * get all store ids
		 */
		$this->_extra_attrs=array();
		/* now is the interesring part */
		/* iterate on attribute backend type index */
		foreach($attmap as $tp=>$a)
		{
			//$this->log("createAttributes ".print_r($attmap, true), "debug");
		
			/* for static types, do not insert into attribute tables */
			if($tp=="static")
			{
				continue;
			}

			//table name for backend type data
			$cpet=$this->tablename("catalog_category_entity_$tp");
			//data table for inserts
			$data=array();
			//inserts to perform on backend type eav
			$inserts=array();
			//deletes to perform on backend type eav
			$deletes=array();

			//use reflection to find special handlers
			$typehandler="handle".ucfirst($tp)."Attribute";
			//iterate on all attribute descriptions for the given backend type
			foreach($a["data"] as $attrdesc)
			{
				//get attribute id
				$attid=$attrdesc["attribute_id"];
				//get attribute value in the item to insert based on code
				$atthandler="handle".ucfirst($attrdesc["attribute_code"])."Attribute";
				$attrcode=$attrdesc["attribute_code"];
				//if the attribute code is no more in item (plugins may have come into the way), continue
				if(!in_array($attrcode,array_keys($item)))
				{
					continue;
				}
				//get the item value
				$ivalue=$item[$attrcode];
				//get item store id for the current attribute
				$store_ids=$this->getItemStoreIds($item,$attrdesc["is_global"]);


				//do not handle empty generic int values in create mode
				if($ivalue=="" && $this->mode!="update" && $tp=="int")
				{
					continue;
				}
				//for all store ids
				foreach($store_ids as $store_id)
				{

					//base output value to be inserted = base source value
					$ovalue=$ivalue;
					//check for attribute handlers for current attribute
					foreach($this->_attributehandlers as $match=>$ah)
					{
						$matchinfo=explode(":",$match);
						$mtype=$matchinfo[0];
						$mtest=$matchinfo[1];
						unset($matchinfo);
						unset($hvalue);
						if(preg_match("/$mtest/",$attrdesc[$mtype]))
						{
							//if there is a specific handler for attribute, use it
							if(method_exists($ah,$atthandler))
							{
								$hvalue=$ah->$atthandler($pid,$item,$store_id,$attrcode,$attrdesc,$ivalue);
							}
							else
							//use generic type attribute
							if(method_exists($ah,$typehandler))
							{
								$hvalue=$ah->$typehandler($pid,$item,$store_id,$attrcode,$attrdesc,$ivalue);
							}
							//if handlers returned a value that is not "__MAGMI_UNHANDLED__" , we have our output value
							if(isset($hvalue) && $hvalue!="__MAGMI_UNHANDLED__")
							{
								$ovalue=$hvalue;
								break;
							}
						}
					}
					//if __MAGMI_UNHANDLED__ ,don't insert anything
					if($ovalue=="__MAGMI_UNHANDLED__")
					{
						$ovalue=false;
					}
					//if handled value is a "DELETE"
					if($ovalue=="__MAGMI_DELETE__")
					{
						$deletes[]=$attid;
					}
					else
					//if we have something to do with this value
					if($ovalue!==false)
					{

						$data[]=$this->cat_etype;
						$data[]=$attid;
						$data[]=$store_id;
						$data[]=$pid;
						$data[]=$ovalue;
						$insstr="(?,?,?,?,?)";
						$inserts[]=$insstr;
					}

					//if one of the store in the list is admin
					if($store_id==0)
					{
						$sids=$store_ids;
						//remove all values bound to the other stores for this attribute,so that they default to "use admin value"
						array_pop($sids);
						if(count($sids)>0)
						{
							$sidlist=implode(",",$sids);
							$ddata=array($this->cat_etype,$attid,$pid);
							$sql="DELETE FROM $cpet WHERE entity_type_id=? AND attribute_id=? AND store_id IN ($sidlist) AND entity_id=?";
							$this->delete($sql,$ddata);
							unset($ddata);
						}
						unset($sids);
						break;
					}
				}
			}



			if(!empty($inserts))
			{
				//now perform insert for all values of the the current backend type in one
				//single insert
                            if($pid == 484){
                                $i =0;
                            }
                            
				$sql="INSERT INTO $cpet
			(`entity_type_id`, `attribute_id`, `store_id`, `entity_id`, `value`)
			VALUES ";
				$sql.=implode(",",$inserts);
				//this one taken from mysql log analysis of magento import
				//smart one :)
				$sql.=" ON DUPLICATE KEY UPDATE `value`=VALUES(`value`)";
				$this->insert($sql,$data);
			}

			if(!empty($deletes))
			{
				$sidlist=implode(",",$store_ids);
				$attidlist=implode(",",$deletes);
				$sql="DELETE FROM $cpet WHERE entity_type_id=? AND attribute_id IN ($attidlist) AND store_id IN ($sidlist) AND entity_id=?";
				$this->delete($sql,array($this->cat_etype,$pid));
			}

			if(empty($deletes) && empty($inserts) && $isnew)
			{
				if(!$this->_same)
				{
					$this->log("No $tp Attributes created for category ".$item["category_id"],"warning");
				}
			}
			unset($store_ids);
			unset($data);
			unset($inserts);
			unset($deletes);
		}
		return $this->_extra_attrs;
	}


	public function getItemWebsites($item,$default=false)
	{
		$k=$item["store"];
		
		if(!isset($this->_wsids[$k]))
		{
				$this->_wsids[$k]=array();
				$cs=$this->tablename("core_store");
				if(trim($k)!="admin")
				{
					$scodes=csl2arr($k);
					$qcolstr=$this->arr2values($scodes);
					$rows=$this->selectAll("SELECT website_id FROM $cs WHERE code IN ($qcolstr) AND store_id!=0 GROUP BY website_id",$scodes);
				}
				else
				{
					$rows=$this->selectAll("SELECT website_id FROM $cs WHERE store_id!=0 GROUP BY website_id ");
				}
				foreach($rows as $row)
				{
					$this->_wsids[$k][]=$row['website_id'];
				}
			}
			return $this->_wsids[$k];
	
	}


	public function handleIgnore(&$item)
	{
		//filter __MAGMI_IGNORE__ COLUMNS
		foreach($item as $k=>$v)
		{
			if($v=="__MAGMI_IGNORE__")
			{
				unset($item[$k]);
			}
		}
	}

	

	public function checkItemStores($scodes)
	{
		if($scodes=="admin")
		{
			return $scodes;
		}

		$scarr=explode(",",$scodes);
		trimarray($scarr);
		$rscode=array();
		$sql="SELECT code FROM ".$this->tablename("core_store")." WHERE code IN (".$this->arr2values($scarr).")";
		$result=$this->selectAll($sql,$scarr);
		$rscodes=array();
		foreach($result as $row)
		{
			$rscodes[]=$row["code"];
		}
		$diff=array_diff($scarr, $rscodes);
		$out="";
		if(count($diff)>0)
		{
			$out="Invalid store code(s) found:".implode(",",$diff);
		}
		if($out!="")
		{
			if(count($rscodes)==0)
			{
				$out.=", NO VALID STORE FOUND";
			}
			$this->log($out,"warning");
		}

		return implode(",",$rscodes);
	}

	public function checkstore(&$item,$pid,$isnew)
	{
		//we have store column set , just check
		if(isset($item["store"]) && trim($item["store"])!="")
		{
			$scodes=$this->checkItemStores($item["store"]);
		}
		else
		{
			$scodes="admin";
		}
		if($scodes=="")
		{
			return false;
		}
		$item["store"]=$scodes;
		return true;
	}
	/**
	 * full import workflow for item
	 * @param array $item : attribute values for product indexed by attribute_code
	 */
	public function importItem($item)
	{

		$this->handleIgnore($item);
		//$this->handleImageColumn($item);
		
		if(Magmi_StateManager::getState()=="canceled")
		{
			exit();
		}
		
		//check if sku has been reset
		if(!isset($item["category_id"]) || trim($item["category_id"])=='')
		{
			$this->log('No category_id info found for record #'.$this->_current_row,"error");
			return false;	
		}
		//handle "computed" ignored columns
		$this->handleIgnore($item);
		$itemids=$this->getItemIds($item);
		$pid=$itemids["pid"];
		$asid=$itemids["asid"];
		$isnew=false;
		if(isset($pid) && $this->mode=="xcreate")
		{
			$this->log("skipping existing category_id:{$item["category_id"]} - xcreate mode set","skip");
			return false;
		}
		if(!isset($pid))
		{

			if($this->mode!=='update_category')
			{
				if(!isset($asid))
				{
					$this->log("cannot create category id:{$item["category_id"]}, no attribute_set defined","error");
					return false;
				}
				$pid=$this->createCategory($item,$asid);
				$this->_curitemids["pid"]=$pid;
				$isnew=true;
			}
			else
			{
				//mode is update, do nothing
				$this->log("skipping unknown category_id:{$item["category_id"]} - update mode set","skip");
				return false;
			}
		}
		else
		{
			$this->updateCategory($item,$pid);
		}
		try
		{
			
			if(count($item)==0)
			{
				return true;
			}
			//handle "computed" ignored columns from afterImport
			$this->handleIgnore($item);

			if(!$this->checkstore($item,$pid,$isnew))
			{
				$this->log("invalid store value, skipping item category_id:".$item["category_id"]);
				return false;
			}
			//create new ones
			$attrmap=$this->attrbytype;
			
			//$this->log("attrmap ".print_r($attrmap, true), "debug");
			do
			{
                            //$this->log("createAttributes ".print_r($item, true), "debug");
                            $attrmap=$this->createAttributes($pid,$item,$attrmap,$isnew);
			}
			while(count($attrmap)>0);

			$this->touchCategory($pid);
			
			//ok,we're done
			if(!$this->callPlugins("itemprocessors","processItemAfterImport",$item,array("category_id"=>$pid,"new"=>$isnew,"same"=>$this->_same)))
			{
				return false;
			}
		}
		catch(Exception $e)
		{
			$this->logException($e,$this->_laststmt->queryString);
			throw $e;
		}
		return true;
	}

	public function getProperties()
	{
		return $this->_props;
	}

        /**
	 * Updateds category update time
	 * @param unknown_type $pid : entity_id of product
	 */
	public function touchCategory($pid)
	{
		$tname=$this->tablename('catalog_category_entity');
		$this->update("UPDATE $tname SET updated_at=? WHERE entity_id=?",array(strftime("%Y-%m-%d %H:%M:%S"),$pid));
	}
        
	/**
	 * count lines of csv file
	 * @param string $csvfile filename
	 */
	public function lookup()
	{
		$t0=microtime(true);
		$this->log("Performing Datasouce Lookup...","startup");

		$count=$this->datasource->getRecordsCount();
		$t1=microtime(true);
		$time=$t1-$t0;
		$this->log("$count:$time","lookup");
		$this->log("Found $count records, took $time sec","startup");

		return $count;
	}



	public function updateCategory($item,$pid)
	{
		$tname=$this->tablename('catalog_category_entity');
		
		$item['entity_type_id']=$this->cat_etype;
		$item['updated_at']=strftime("%Y-%m-%d %H:%M:%S");
		$columns=array_intersect(array_keys($item), $this->getCatCols());
		$values=$this->filterkvarr($item, $columns);

		$sql="UPDATE  `$tname` SET ".$this->arr2update($values). " WHERE entity_id=?";
		$this->update($sql,array_merge(array_values($values),array($pid)));

	}

	public function getCategoryEntityType()
	{
		return $this->cat_etype;
	}
	
	public function getCurrentRow()
	{
		return $this->_current_row;
	}

	public function setCurrentRow($cnum)
	{
		$this->_current_row=$cnum;
	}

	public function isLastItem($item)
	{
		return isset($item["__MAGMI_LAST__"]);
	}

	public function setLastItem(&$item)
	{
		$item["__MAGMI_LAST__"]=1;
	}

	public function engineInit($params)
	{
		$this->_profile=$this->getParam($params,"profile","default");
		$this->initPlugins($this->_profile);
		$this->mode=$this->getParam($params,"mode","update");

	}


	public function reportStats($nrow,&$tstart,&$tdiff,&$lastdbtime,&$lastrec)
	{
		$tend=microtime(true);
		$this->log($nrow." - ".($tend-$tstart)." - ".($tend-$tdiff),"itime");
		$this->log($this->_nreq." - ".($this->_indbtime)." - ".($this->_indbtime-$lastdbtime)." - ".($this->_nreq-$lastrec),"dbtime");
		$lastrec=$this->_nreq;
		$lastdbtime=$this->_indbtime;
		$tdiff=microtime(true);
	}

        public function getItemIds($item)
	{
		$category_id=$item["category_id"];
                
		if($category_id!=$this->_curitemids["category_id"])
		{
			//try to find item ids in db
			$cids=$this->getCategoryIds($category_id);
			if($cids!==false)
			{
				//if found use it
				$this->_curitemids=$cids;
			}
			else
			{
                                if(empty($item["attribute_set"])) $item["attribute_set"] = "Default";
                                
				//only sku & attribute set id from datasource otherwise.
				$this->_curitemids=array("pid"=>null,"category_id"=>$category_id,"asid"=>isset($item["attribute_set"])?$this->getAttributeSetId($item["attribute_set"]):null);
			}
			//do not reset values for existing if non admin
			$this->onNewId($category_id,($cids!==false));
			unset($cids);
		}
		else
		{
			$this->onSameId($category_id);
		}
		return $this->_curitemids;
	}
        
        public function clearOptCache()
	{
		unset($this->_optidcache);
		$this->_optidcache=array();
	}
        
        public function onNewId($id, $existing)
	{
		$this->clearOptCache();
		//only assign values to store 0 by default in create mode for new sku
		//for store related options
		if(!$existing)
		{
			$this->_dstore=array(0);
		}
		else
		{
			$this->_dstore=array();
		}
		$this->_same=false;
	}
        
        public function onSameId($id)
	{
		unset($this->_dstore);
		$this->_dstore=array();
		$this->_same=true;
	}


	public function initImport($params)
	{
		$this->log("Import Mode:$this->mode","startup");
		$this->log("MAGMI by dweeves - version:".Magmi_Version::$version,"title");
		$this->log("step:".$this->getProp("GLOBAL","step",0.5)."%","step");
		//intialize store id cache
		$this->connectToMagento();
		try
		{
			$this->initCatType();
			$this->createPlugins($this->_profile,$params);
			$this->_registerPluginLoopCallback("processItemAfterId", "onPluginProcessedItemAfterId");
			$this->callPlugins("datasources,itemprocessors","startImport");
            $this->resetSkuStats();
		}
		catch(Exception $e)
		{
		 $this->disconnectFromMagento();
		}
	}

	public function onPluginProcessedItemAfterId($plinst,&$item,$plresult)
	{
		$this->handleIgnore($item);
	}
	
	public function exitImport()
	{
		$this->callPlugins("datasources,general,itemprocessors","endImport");
		$this->callPlugins("datasources,general,itemprocessors","afterImport");
		$this->disconnectFromMagento();
	}

	public function updateSkuStats($res)
	{
		if(!$this->_same)
		{
			$this->_skustats["nsku"]++;
			if($res["ok"])
			{
				$this->_skustats["ok"]++;	
			}
			else
			{
				$this->_skustats["ko"]++;
			}
		}
	}
	public function getDataSource()
	{
		return $this->getPluginInstance("datasources");

	}

        public function registerAttributeHandler($ahinst,$attdeflist)
	{
		foreach($attdeflist as $attdef)
		{
			$ad=explode(":",$attdef);
			if(count($ad)!=2)
			{
				$this->log("Invalid registration string ($attdef) :".get_class($ahinst),"warning");
			}
			else
			{
				$this->_attributehandlers[$attdef]=$ahinst;
			}
		}
	}
        
	public function processDataSourceLine($item,$rstep,&$tstart,&$tdiff,&$lastdbtime,&$lastrec)
	{
		//counter
		$res=array("ok"=>0,"last"=>0);
		$this->_current_row++;
		if($this->_current_row%$rstep==0)
		{
			$this->reportStats($this->_current_row,$tstart,$tdiff,$lastdbtime,$lastrec);
		}
		try
		{
			if(is_array($item) && count($item)>0)
			{
				//import item
				$this->beginTransaction();
				$importedok=$this->importItem($item);
				if($importedok)
				{
					$res["ok"]=true;
					$this->commitTransaction();
				}
				else
				{
					$res["ok"]=false;
					$this->rollbackTransaction();

				}
			}
			else
			{
				$this->log("ERROR - RECORD #$this->_current_row - INVALID RECORD","error");
			}
			//intermediary measurement

		}
		catch(Exception $e)
		{
			$this->rollbackTransaction();
			$res["ok"]=false;
			$this->logException($e,"ERROR ON RECORD #$this->_current_row");
		}
		if($this->isLastItem($item))
		{
			unset($item);
			$res["last"]=1;
		}
		
		unset($item);
		$this->updateSkuStats($res);
		
		return $res;

	}

	public function resetSkuStats()
	{
		 $this->_skustats=array("nsku"=>0,"ok"=>0,"ko"=>0);
	}
        
        public function getSkuStats()
	{
		return $this->_skustats;
	}
	
	
	public function engineRun($params,$forcebuiltin=array())
	{
		$this->log("Import Mode:$this->mode","startup");
		$this->log("MAGMI by dweeves - version:".Magmi_Version::$version,"title");
		$this->log("step:".$this->getProp("GLOBAL","step",0.5)."%","step");
		$this->createPlugins($this->_profile,$params);
		$this->datasource=$this->getDataSource();
		$this->callPlugins("datasources,general","beforeImport");
		$nitems=$this->lookup();
		Magmi_StateManager::setState("running");
		//if some rows found
		if($nitems>0)
		{
			$this->resetSkuStats();
			//intialize store id cache
			$this->callPlugins("datasources,itemprocessors","startImport");
			//initializing item processors
			$cols=$this->datasource->getColumnNames();
			$this->log(count($cols),"columns");
			$this->callPlugins("itemprocessors","processColumnList",$cols);
			$this->log("Ajusted processed columns:".count($cols),"startup");
			$this->initCatType();
			//initialize attribute infos & indexes from column names
			if($this->mode!="update")
			{
				$this->checkRequired($cols);
			}
			$this->initAttrInfos(array_values($cols));
			//counter
			$this->_current_row=0;
			//start time
			$tstart=microtime(true);
			//differential
			$tdiff=$tstart;
			//intermediary report step
			$this->initDbqStats();
			$pstep=$this->getProp("GLOBAL","step",0.5);
			$rstep=ceil(($nitems*$pstep)/100);
			//read each line
			$lastrec=0;
			$lastdbtime=0;
			while(($item=$this->datasource->getNextRecord())!==false)
			{
				 $res=$this->processDataSourceLine($item, $rstep,$tstart,$tdiff,$lastdbtime,$lastrec);
				 //break on "forced" last
				 if($res["last"])
				 {
				 	break;
				 }
			}
			$this->callPlugins("datasources,general,itemprocessors","endImport");
			$this->reportStats($this->_current_row,$tstart,$tdiff,$lastdbtime,$lastrec);
			$this->log("Skus imported OK:".$this->_skustats["ok"]."/".$this->_skustats["nsku"],"info");
			if($this->_skustats["ko"]>0)
			{
				$this->log("Skus imported KO:".$this->_skustats["ko"]."/".$this->_skustats["nsku"],"warning");
			}
		}
		else
		{
			$this->log("No Records returned by datasource","warning");
		}
		$this->callPlugins("datasources,general,itemprocessors","afterImport");
		
		$this->log("Import Ended","end");
		Magmi_StateManager::setState("idle");
	}

	public function onEngineException($e)
	{
		if(isset($this->datasource))
		{
			$this->datasource->onException($e);
		}
		$this->log("Import Ended","end");

		Magmi_StateManager::setState("idle");
	}

}
