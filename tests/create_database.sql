create user hermestests;
create schema hermestests;
grant all on hermestests.* to 'hermestests'@'%';
grant all on hermestests.* to 'hermestests'@'localhost';
set password for 'hermestests'@'%' = password('dev123');
set password for 'hermestests'@'localhost' = password('dev123');
