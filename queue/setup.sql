create user hermes;
create schema hermes;
grant all on hermes.* to 'hermes'@'%';
grant all on hermes.* to 'hermes'@'localhost';
set password for 'hermes'@'%' = password('dev123');
set password for 'hermes'@'localhost' = password('dev123');

