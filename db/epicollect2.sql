CREATE TABLE IF NOT EXISTS `device` (
  `idDevice` int(11) NOT NULL AUTO_INCREMENT,
  `DeviceType` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`idDevice`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ~

CREATE TABLE IF NOT EXISTS `deviceuser` (
  `idDeviceUser` int(11) NOT NULL AUTO_INCREMENT,
  `device` int(11) DEFAULT NULL,
  `user` int(11) DEFAULT NULL,
  PRIMARY KEY (`idDeviceUser`),
  KEY `fk_DeviceUser_User1` (`user`),
  KEY `fk_DeviceUser_Device1` (`device`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ~

CREATE TABLE IF NOT EXISTS `enterprise` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name_UNIQUE` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Initially redundanty' AUTO_INCREMENT=1 ~

CREATE TABLE IF NOT EXISTS `entry` (
  `idEntry` int(11) NOT NULL AUTO_INCREMENT,
  `form` int(11) NOT NULL,
  `projectName` varchar(255) NOT NULL,
  `formName` varchar(100) NOT NULL,
  `DeviceId` varchar(50) NOT NULL,
  `created` bigint(20) unsigned NOT NULL,
  `lastEdited` datetime DEFAULT NULL,
  `uploaded` datetime NOT NULL,
  `user` int(11) NOT NULL,
  PRIMARY KEY (`idEntry`),
  KEY `fk_Entry_Form1` (`form`),
  KEY `fk_Entry_User1` (`user`),
  KEY `projectName` (`projectName`),
  KEY `formName` (`formName`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=11960 ~

CREATE TABLE IF NOT EXISTS `entryvalue` (
  `idEntryValue` int(11) NOT NULL AUTO_INCREMENT,
  `field` int(11) NOT NULL,
  `projectName` varchar(255) NOT NULL,
  `formName` varchar(100) NOT NULL,
  `fieldName` varchar(45) NOT NULL,
  `value` varchar(1000) DEFAULT NULL,
  `entry` int(11) NOT NULL,
  PRIMARY KEY (`idEntryValue`),
  KEY `value` (`value`(255)),
  KEY `fieldname` (`fieldName`) USING HASH,
  KEY `formname` (`formName`) USING HASH,
  KEY `entry` (`entry`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=74778 ~
CREATE TRIGGER `EntryInsert` AFTER INSERT ON `entryvalue`
 FOR EACH ROW BEGIN
	INSERT INTO entryvaluehistory (idEntryValue, projectName, formName, fieldName, value, entry, field, updated)
		VALUES(NEW.idEntryValue, NEW.projectName, NEW.formName, NEW.fieldName, NEW.value, NEW.entry, NEW.field, Now());
END ~
CREATE TRIGGER `EntryUpdate` AFTER UPDATE ON `entryvalue`
 FOR EACH ROW BEGIN
	INSERT INTO entryvaluehistory (idEntryValue, projectName, formName, fieldName, value, entry, field, updated)
		VALUES(NEW.idEntryValue, NEW.projectName, NEW.formName, NEW.fieldName, NEW.value, NEW.entry, NEW.field, Now());
END ~

CREATE TABLE IF NOT EXISTS `entryvaluehistory` (
  `idEntryValue` int(11) DEFAULT NULL,
  `value` varchar(1000) DEFAULT NULL,
  `entry` int(11) DEFAULT NULL,
  `field` int(11) DEFAULT NULL,
  `updated` datetime DEFAULT NULL,
  `projectName` varchar(255) NOT NULL,
  `formName` varchar(100) NOT NULL,
  `fieldName` varchar(45) NOT NULL
) ENGINE=ARCHIVE DEFAULT CHARSET=utf8 ~

CREATE TABLE IF NOT EXISTS `field` (
  `idField` int(11) NOT NULL AUTO_INCREMENT,
  `form` int(11) NOT NULL,
  `projectName` varchar(255) NOT NULL,
  `formName` varchar(100) NOT NULL,
  `type` int(11) NOT NULL,
  `name` varchar(45) NOT NULL,
  `label` varchar(1000) NOT NULL,
  `language` varchar(45) NOT NULL DEFAULT 'EN',
  `regex` varchar(45) DEFAULT NULL,
  `title` tinyint(1) NOT NULL DEFAULT '0',
  `key` tinyint(1) NOT NULL DEFAULT '0',
  `isinteger` tinyint(1) NOT NULL DEFAULT '0',
  `isdouble` tinyint(1) NOT NULL DEFAULT b'0',
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `doubleEntry` tinyint(1) NOT NULL DEFAULT '0',
  `jump` varchar(1024) DEFAULT NULL,
  `required` tinyint(1) NOT NULL,
  `search` tinyint(1) NOT NULL DEFAULT b'0',
  `display` tinyint(1) NOT NULL DEFAULT b'1',
  `group_form` varchar(255) DEFAULT NULL,
  `branch_form` varchar(255) DEFAULT NULL,
  `date` varchar(255) DEFAULT NULL,
  `time` varchar(255) DEFAULT NULL,
  `settime` varchar(255) DEFAULT NULL,
  `setdate` varchar(255) DEFAULT NULL,
  `genkey` tinyint(1) NOT NULL DEFAULT b'0',
  `min` double DEFAULT NULL,
  `max` double DEFAULT NULL,
  `crumb` varchar(255) DEFAULT NULL,
  `match` varchar(255) DEFAULT NULL,
  `defaultValue` varchar(1000) DEFAULT NULL,
  `position` int(11) NOT NULL COMMENT 'zero-indexed position in the form.',
  PRIMARY KEY (`idField`),
  KEY `fk_form` (`form`),
  KEY `fk_field` (`type`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=0 ~

CREATE TABLE IF NOT EXISTS `fieldtype` (
  `idFieldType` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(45) DEFAULT NULL,
  `formbuilderLabel` varchar(40) NOT NULL,
  `ctrlHtml` varchar(255) DEFAULT NULL,
  `optionHtml` varchar(1000) DEFAULT NULL,
  `hasOptions` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`idFieldType`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ~

CREATE TABLE IF NOT EXISTS `form` (
  `idForm` int(11) NOT NULL AUTO_INCREMENT,
  `project` int(11) NOT NULL,
  `projectName` varchar(255) NOT NULL,
  `version` float NOT NULL DEFAULT '1',
  `name` varchar(100) NOT NULL,
  `isMain` tinyint(1) NOT NULL DEFAULT b'1',
  `table_num` int(11) NOT NULL DEFAULT '1',
  `keyField` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`idForm`),
  KEY `fk_Form_Project1` (`project`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=39 ~

CREATE TABLE IF NOT EXISTS `option` (
  `idoption` int(11) NOT NULL AUTO_INCREMENT,
  `index` int(11) NOT NULL,
  `label` varchar(255) NOT NULL,
  `value` varchar(255) NOT NULL,
  `field` int(11) DEFAULT NULL,
  PRIMARY KEY (`idoption`),
  KEY `FK_option_field` (`field`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ~

CREATE TABLE IF NOT EXISTS `project` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL COMMENT 'The unique name of the project used to identify it in URLs',
  `description` text,
  `image` varchar(45) DEFAULT NULL,
  `enterprise` int(11) DEFAULT NULL,
  `isPublic` tinyint(1) NOT NULL DEFAULT '1',
  `isListed` tinyint(1) NOT NULL DEFAULT '1',
  `publicSubmission` tinyint(1) NOT NULL DEFAULT '1',
  `submission_id` varchar(255) NOT NULL,
  `uploadToLocalServer` varchar(1024) DEFAULT NULL,
  `downloadFromLocalServer` varchar(1024) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name_UNIQUE` (`name`),
  UNIQUE KEY `submission_id` (`submission_id`),
  KEY `FK_enterprise` (`enterprise`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='The top-level project data' AUTO_INCREMENT=11 ~

CREATE TABLE IF NOT EXISTS `role` (
  `idRole` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`idRole`),
  UNIQUE KEY `name_UNIQUE` (`name`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=4 ~

CREATE TABLE IF NOT EXISTS `submissionhistory` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `firstUploaded` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `request` text,
  `attempts` int(11) NOT NULL DEFAULT '1',
  `result` tinyint(1) NOT NULL DEFAULT b'0',
  `message` varchar(1000) DEFAULT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ~

CREATE TABLE IF NOT EXISTS `user` (
  `idUsers` int(11) NOT NULL AUTO_INCREMENT,
  `FirstName` varchar(255) DEFAULT NULL,
  `LastName` varchar(255) DEFAULT NULL,
  `Email` varchar(255) NOT NULL,
  `details` TEXT DEFAULT NULL,
  `language` varchar(8) DEFAULT NULL,
  `serverManager` tinyint(1) DEFAULT b'0',
  PRIMARY KEY (`idUsers`),
  UNIQUE KEY `Email` (`Email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ~

CREATE TABLE IF NOT EXISTS `userprojectpermission` (
  `idUserProjectPermission` int(11) NOT NULL AUTO_INCREMENT,
  `user` int(11) DEFAULT NULL,
  `project` int(11) DEFAULT NULL,
  `role` int(11) DEFAULT NULL,
  PRIMARY KEY (`idUserProjectPermission`),
  KEY `fk_user` (`user`),
  KEY `fk_project` (`project`),
  KEY `fk_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ~

ALTER TABLE `deviceuser`
  ADD CONSTRAINT `fk_DeviceUser_Device1` FOREIGN KEY (`device`) REFERENCES `device` (`idDevice`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_DeviceUser_User1` FOREIGN KEY (`user`) REFERENCES `user` (`idUsers`) ON DELETE NO ACTION ON UPDATE NO ACTION~

ALTER TABLE `field`
  ADD CONSTRAINT `fk_field` FOREIGN KEY (`type`) REFERENCES `fieldtype` (`idFieldType`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_form` FOREIGN KEY (`form`) REFERENCES `form` (`idForm`) ON DELETE NO ACTION ON UPDATE NO ACTION~

ALTER TABLE `form`
  ADD CONSTRAINT `fk_Form_Project1` FOREIGN KEY (`project`) REFERENCES `project` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION~

ALTER TABLE `option`
  ADD CONSTRAINT `FK_option_field` FOREIGN KEY (`field`) REFERENCES `field` (`idField`) ON DELETE NO ACTION ON UPDATE NO ACTION~

ALTER TABLE `project`
  ADD CONSTRAINT `FK_enterprise` FOREIGN KEY (`enterprise`) REFERENCES `enterprise` (`id`)~

ALTER TABLE `userprojectpermission`
  ADD CONSTRAINT `fk_project` FOREIGN KEY (`project`) REFERENCES `project` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_role` FOREIGN KEY (`role`) REFERENCES `role` (`idRole`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_user` FOREIGN KEY (`user`) REFERENCES `user` (`idUsers`) ON DELETE NO ACTION ON UPDATE NO ACTION~

CREATE PROCEDURE `addAdmin`(prj INT, adm INT)
BEGIN
    INSERT INTO userprojectpermission (user, project, role) VALUES (adm, prj, 3);
END ~

CREATE PROCEDURE `addField`(pName varchar(255), formName varchar(255), fieldId varchar(255), fieldLabel varchar(255), typeName varchar(255), labelLanguage varchar(2),
    regex varchar(255), isTitle tinyint, isKey tinyint, isInt tinyint, isDouble tinyint, isActive tinyint, isDoubelEntry tinyint, jump varchar(255))
BEGIN
    declare prjId int;
    declare frmId int;
    declare typeId int;

    select id into prjId from project where `name` = pName;
    select idForm into frmId from form where project = prjId and `name` = formName;
    select idFieldType into typeId from fieldtype where `name` = typeName;
    INSERT INTO field (form, `type`, `name`, `label`, `language`, regex, title, `key`, isinteger, isdouble, active, doubleEntry, jump)
        VALUES (frmId, typeId, fieldId, fieldLabel, labelLanguage, regex, isTitle, isKey, isInt, isDouble, isActive, isDoubelEntry, jump);

END ~

CREATE PROCEDURE `addForm`(pName varchar(255), form_name varchar(255), form_number INT, version Float)
BEGIN
    declare prjId INT;
    select id into prjId from project where `name` = pName;
	INSERT INTO form (project, version, table_num, `name`) VALUES (prjId, version, form_number, form_name);
END ~

CREATE PROCEDURE `addOption`(prj Varchar(255), frm varchar(255), fld varchar(255), idx int, lbl varchar(255), val varchar(255))
BEGIN
	declare fldID INT;
    select idField into fldId from field where projectName = prj and formName = frm and `name` = fld;
    INSERT INTO `option` (`index`, `label`, `value`, `field`) VALUES (idx, lbl, val, fldId);
END ~

CREATE PROCEDURE `addproject`(pName VARCHAR(255), submissionId varchar(255), pDescription TEXT, pImage varchar(255), pIsPublic tinyint, pIsListed tinyint, pPublicSubmission tinyint, creator INT)
BEGIN
    INSERT INTO project (`name`, `submission_id`, `description`, `image`, `isPublic`, `isListed`, `publicSubmission`) VALUES (pName, submissionId, pDescription, pImage, pIsPublic, pIsListed,pPublicSubmission);
END ~

CREATE PROCEDURE `addSubmitter`(prj INT, sub INT)
BEGIN
    INSERT INTO userprojectpermission (user, project, role) VALUES (sub, prj, 1);
END ~

CREATE PROCEDURE `addUser`(prj INT, usr INT)
BEGIN
    INSERT INTO userprojectpermission (user, project, role) VALUES (usr, prj, 2);
END ~

CREATE PROCEDURE `checkProjectPermission`(userId INT, projectId int)
BEGIN
    select role from userprojectpermission where `user` = userId and project = projectId;
END ~

CREATE PROCEDURE `clearPermissions`(prj INT, adm INT)
BEGIN
    delete from userprojectpermission where project = prj and user <> adm;
END ~

CREATE PROCEDURE `deleteProject`(prjName varchar(255))
BEGIN
	
	DELETE FROM entryvalue where projectName = prjName;
	DELETE FROM entry where projectName = prjName;
	DELETE FROM `option` where field in (SELECT idField FROM field WHERE projectName = prjName);
	DELETE FROM field where projectName = prjName;
	DELETE FROM form where projectName = prjName;
	DELETE FROM userProjectPermission where project in (select id from project where name = prjName);
	DELETE FROM project where name = prjName;
END ~


CREATE PROCEDURE `getFields`(frm int)
BEGIN
	select f.idField, f.name, f.label, f.regex, f.title, f.key, f.isInteger, f.isDouble, f.doubleEntry, f.jump, ft.name as type, ft.hasOptions from field f left join fieldtype ft on f.type = ft.idFieldType where form = frm;
END ~

CREATE PROCEDURE `getForm`(frmName varchar(255), version double)
BEGIN
    select * from form where `name` = frmName and version = version;
END ~

CREATE PROCEDURE `getFormFields`(frmName varchar(255), version double)
BEGIN
    declare frmID int;
    select frmID = id from form where `name` = frmName and version = version;
    select * from field where form = frmID;
END ~

CREATE  PROCEDURE `getForms`(prj Varchar(255))
BEGIN
	select f.* from form f left join project p on f.project = p.id  where p.name = prj;
END ~

CREATE PROCEDURE `getOptions`(fld int)
BEGIN
select * from `option` where field = fld;
END ~

CREATE PROCEDURE `getProject`(pName VARCHAR(255))
BEGIN
    IF pName is not null then
        SELECT * FROM project WHERE `name` = pName;
    else
        SELECT * FROM project where isListed = 1;
    end if;
END ~

CREATE PROCEDURE `getProjectPeople`(prj int)
BEGIN
    SELECT User, role from userprojectpermission where project = prj;
END ~

CREATE PROCEDURE `getUser`(id INT)
BEGIN
    SELECT * from user where idUsers = id;
END ~

CREATE PROCEDURE `updateEcUser`(id INT, RealName varchar(255), newemail varchar(255))
BEGIN
    UPDATE `user` SET `Name` = RealName, Email = newemail where idUsers = id;
END ~

CREATE PROCEDURE `updateProject`(pId int, pName VARCHAR(255), pDescription TEXT, pImage varchar(255), pIsPublic tinyint, pIsListed tinyint, pPublicSubmission tinyint)
BEGIN
    UPDATE project set `name` = pName, `description` = pDescription, `image` = pImage, `isPublic` = pIsPublic, `isListed` = pIsListed, `publicSubmission` = pPublicSubmission
        WHERE `id`= pId;
END ~

CREATE PROCEDURE `updateUser`(id INT, uName VARCHAR(100), uEmail VARCHAR(100))
BEGIN
   UPDATE `user` SET `Name` = uName, `Email` =uEmail where idUsers = id; 
END ~

CREATE PROCEDURE `userSearch`(search varchar(255))
BEGIN
    Select idUsers, `Name` from user where `Name` Like search or Email like search
    UNION 
    SELECT uoa.user as idUsers, u.Name as Name from useroauth uoa, user u where uoa.user = u.idUsers and (nickname Like search or providerUserId like search);
END ~

INSERT INTO `fieldtype` (`idFieldType`, `name`, `formbuilderLabel`, `ctrlHtml`, `optionHtml`, `hasOptions`) VALUES
(1, 'input', 'Text Field', '<input type="text"></input>', NULL, 0),
(2, 'textarea', 'Long Text Field', '<textarea></textarea>', NULL, 0),
(3, 'select', 'Select Multiple', '<div></div>', '<input type="checkbox" />', 1),
(4, 'select1', 'Drop Down List', '<select></select>', '<option value="one">one</option>', 1),
(5, 'barcode', 'Barcode', '<input type="text"/>', NULL, 0),
(6, 'photo', 'Photograph', '<input type="file" value="" />', NULL, 0),
(7, 'video', 'Video', '<input type="file"/>', NULL, 0),
(8, 'audio', 'Audio', '<input type="file"/>', NULL, 0),
(10, 'gps', 'GPS', '<input type="text" />', NULL, 0),
(11, 'radio', 'Select One', '<div></div>', '<input type="radio" />', 1),
(12, 'group', 'Group field', '<select></select>', '<option value="one">one</option>', 1),
(13, 'branch', 'Branch field', '<input />', NULL, 0),
(14, 'location', 'Location', '<input type="text" />', NULL, 0) ~

INSERT INTO `role` (`idRole`, `name`) VALUES
(1, 'collector'),
(2, 'curator'),
(3, 'manager')~

CREATE TABLE `logs` (
	`Timestamp` BIGINT NOT NULL,
	`Type` VARCHAR(50) NOT NULL,
	`Message` TEXT NOT NULL
) ENGINE=ARCHIVE DEFAULT CHARSET=utf8 ~


CREATE TABLE `ecsession` (
	`id` VARCHAR(255) NOT NULL,
	`user` INT NOT NULL,
	`expires` BIGINT,
	PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ~