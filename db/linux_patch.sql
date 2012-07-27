DELIMITER ~
DROP PROCEDURE `deleteProject` ~
CREATE PROCEDURE `deleteProject`(prjName varchar(255))
BEGIN
	
	DELETE FROM entryvalue where projectName = prjName;
	DELETE FROM entry where projectName = prjName;
	DELETE FROM `option` where field in (SELECT idField FROM field WHERE projectName = prjName);
	DELETE FROM field where projectName = prjName;
	DELETE FROM form where projectName = prjName;
	DELETE FROM userprojectpermission where project in (select id from project where name = prjName);
	DELETE FROM project where name = prjName;
END ~