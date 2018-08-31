CREATE TABLE tx_dce_domain_model_dce (
	tx_hugo_typename varchar(255),
);

CREATE TABLE sys_domain (
	tx_hugo_domains varchar(255),
);


CREATE TABLE queue_items (
  uid int(11) NOT NULL auto_increment,
  namespace varchar (32) NOT NULL,
  value varchar(128) NOT NULL,
  executed tinyint(4) NOT NULL DEFAULT '0',
  created_date DATETIME,
  executed_date DATETIME,
  PRIMARY KEY (uid),
  KEY namespace (namespace, value),
	KEY executed (executed, created_date),
);
