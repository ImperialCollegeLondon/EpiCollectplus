create procedure getFields(frm int)
BEGIN
	select f.idField, f.name, f.label, f.regex, f.title, f.key, f.isInteger, f.isDouble, f.doubleEntry, f.jump, ft.name as type, ft.hasOptions from field f left join fieldType ft on f.type = ft.idFieldType where form = frm;
END$