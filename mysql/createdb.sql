CREATE DATABASE OMS_USAGE;

USE OMS_USAGE;

GRANT ALL on OMS_USAGE.* to 'username'@'oms-vm-ip' IDENTIFIED BY 'password';


CREATE TABLE CONF_CHANGES(
    id INT NOT NULL AUTO_INCREMENT,
    username VARCHAR(20) NOT NULL,
    timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    cores FLOAT NOT NULL,
    disk FLOAT NOT NULL ,
    memory FLOAT NOT NULL,
    number_of_vms INT,
    last_known_credit FLOAT,
    processed BOOLEAN NOT NULL DEFAULT 0,
    PRIMARY KEY (id)
);

