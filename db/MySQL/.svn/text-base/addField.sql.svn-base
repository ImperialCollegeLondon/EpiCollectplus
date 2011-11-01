CREATE PROCEDURE `addField`(pName varchar(255),
		formName varchar(255), fieldId varchar(255), fieldLabel varchar(255),
		typeName varchar(255), labelLanguage varchar(2),
    regex varchar(255), isTitle bit, isKey bit, isInt bit, isDouble bit, isActive bit,
		isDoubleEntry bit, jump varchar(255))
BEGIN
    declare prjId int;
    declare frmId int;
    declare typeId int;

    select id into prjId from project where `name` = pName;
    select idForm into frmId from form where project = prjId and `name` = formName;
    select idFieldType into typeId from fieldtype where `name` = typeName;
    INSERT INTO Field (form, `type`, `name`, `label`, `language`, regex, title, `key`, isinteger, isdouble, active, doubleEntry, jump)
        VALUES (frmId, typeId, fieldId, fieldLabel, labelLanguage, regex, isTitle, isKey, isInt, isDouble, isActive, isDoubleEntry, jump);

END