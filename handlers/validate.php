<?php

function validate($fn = NULL, $xml = NULL, &$name = NULL, $update = false, $returnJson = false) {
    global $SITE_ROOT;

    $isValid = true;
    $msgs = array();

    if (!$fn)
        $fn = getValIfExists($_GET, "filename");

    if ($fn && !$xml) {

        $xml = file_get_contents("./ec/xml/$fn");
    }

    $prj = new EcProject;
    try {
        $prj->parse($xml);
    } catch (Exception $err) {
        array_push($msgs, "The XML for this project is invalid : " . $err->getMessage());
    }

    if (count($msgs) == 0) {
        $prj->name = trim($prj->name);

        if (!$update && EcProject::projectExists($prj->name)) {
            array_push($msgs, sprintf('A project called %s already exists.', $prj->name));
        }

        if (!$prj->name || $prj->name == "") {
            array_push($msgs, "This project does not have a name, please include a projectName attribute in the model tag.");
        }

        if (!$prj->ecVersionNumber || $prj->ecVersionNumber == "") {
            array_push($msgs, "Projects must specify a version");
        }

        if (count($prj->tables) == 0)
            array_push($msgs, "A project must contain at least one table.");

        foreach ($prj->tables as $tbl) {
            if ($tbl->number <= 0)
                continue;
            if (!$tbl->name || $tbl->name == "")
                array_push($msgs, "Each form must have a name.");
            if (!$tbl->key || $tbl->key == "") {
                array_push($msgs, "Each form must have a unique key field.");
            } elseif (!$tbl->fields[$tbl->key]) {
                array_push($msgs, "The form {$tbl->name} does not have a field called {$tbl->key}, please specify another key field.");
            } elseif (!preg_match("/input|barcode/", $tbl->fields[$tbl->key]->type)) {
                array_push($msgs, "The field {$tbl->key} in the form {$tbl->name} is a {$tbl->fields[$tbl->key]->type} field. All key fields must be either text inputs or barcodes.");
            }

            //array_push($msgs, "<b>$tbl->name</b>");
            foreach ($tbl->fields as $fld) {
                if (!preg_match("/^[a-z][a-z0-9_]*$/i", $fld->name) || $fld->name == '') {
                    $isValid = false;
                    array_push($msgs, "The field {$fld->name} in the form {$tbl->name} has an invalid name the field name must : <ul><li>Start with a letter</li><li> Contain only letters, numbers and underscores (_)</li></ul>");
                }
                if (!$fld->label || $fld->label == '') {
                    $isValid = false;
                    array_push($msgs, "The field {$fld->name} in the form {$tbl->name} has no label. All fields must have a label and the label must not be null. If you have added a label to the field please make sure the tags are all in lower case i.e. <label>...</label> not <Label>...</Label>");
                }

                if ($fld->jump) {
                    //break the jump up into it's parts
                    $jBits = explode(",", $fld->jump);
                    if (count($jBits) % 2 != 0) {
                        $isValid = false;
                        array_push($msgs, "The field called {$fld->name} in the form {$tbl->name} has an invalid jump attribute. All jumps should be in the format value,target");
                    }

                    for ($i = 0; $i + 1 < count($jBits); $i += 2) {
                        $jBits[$i] = trim($jBits[$i]);
                        $jBits[$i + 1] = trim($jBits[$i + 1]);
                        //check that the jump destination exists in the current form
                        if (!preg_match('/END/i', $jBits[$i]) && !array_key_exists($jBits[$i], $tbl->fields)) {
                            $isValid = false;
                            array_push($msgs, "The field {$fld->name} in the form {$tbl->name} has an invalid jump statement the field {$jBits[$i]} that is the target when the value is {$jBits[$i+1]} does not exist in this form");
                        }
                        //check that the jump value exists in the form
                        if (($fld->type == "select1" || $fld->type == "radio") && !preg_match('/^(all|null)$/i', $jBits[$i + 1])) {
                            $tval = preg_replace('/^\!/', '', $jBits[$i + 1]);
                            $ival = intval($tval);
                            /**
                             * This if is a total pain.
                             * Long story short, it's supposed to check if the jump condition is valid, so is and integer between 1
                             * and the number of options, ALL or NULL /i is 'cause I'm not sure if everythings coded as all upper
                             * case.
                             *  13/11/2013 - all and null now handled in the top bit, and only enter this clause if the vaue isn't all or null
                             *      intval turns non-numbers to 0 and will hence fail the test.
                             */

                            if ($ival > count($fld->options) || $ival <= 0) {
                                $isValid = false;
                                array_push($msgs, "The field {$fld->name} in the form {$tbl->name} has an invalid jump statement. The jump to {$jBits[$i]} is set to happen when {$jBits[$i+1]} is selected. The Jump condition must be between 1 and " . (count($fld->options)) . " 'NULL' or 'ALL'");
                            }
                        } elseif ($fld->type == "select") {
                            $found = false;
                            for ($o = 0; $o < count($fld->options); $o++) {
                                if (preg_match("/^!?" . $fld->options[$o]->value . "$/", $jBits[$i + 1])) {
                                    $found = true;
                                    break;
                                }
                            }
                            if (!$found) {
                                $isValid = false;
                                array_push($msgs, "The field {$fld->name} in the form {$tbl->name} has an invalid jump statement the jump to {$jBits[$i]} is set to happen when this field is {$jBits[$i+1]}. This value does not exist as an option.");
                            }
                        } elseif ($fld->type == 'numeric') {
                            if (!preg_match('/NULL|all/i', $jBits[$i + 1])) {
                                $v = intval($jBits[$i + 1], 10);
                                if ($fld->max && $v > $fld->max) {
                                    $isValid = false;
                                    array_push($msgs, "The field {$fld->name} in the form {$tbl->name} has an invalid jump statement, the jump value exceeds the fields maximum;");
                                }
                                if ($fld->min && $v < $fld->min) {
                                    $isValid = false;
                                    array_push($msgs, "The field {$fld->name} in the form {$tbl->name} has an invalid jump statement, the jump value is less than the fields maximum;");
                                }
                            }
                        }

                    }
                }
                if ($fld->type == "group") {
                    //make sure the group form exists
                    if (!$fld->group_form) {
                        $isValid = false;
                        array_push($msgs, "The field {$fld->name} is a group form but has no group attribute.");
                    }
                    /*elseif(!array_key_exists($fld->group_form, $prj->tables))
					{
						$isValid = false;
						array_push($msgs, "The field {$fld->name} in the form {$tbl->name} has the form {$fld->group_form} set as it's group form, but the form {$fld->group_form} doesn not exist.");
					}*/
                }
                if ($fld->type == "branch") {
                    //make sure the branch form exists
                    if (!array_key_exists($fld->branch_form, $prj->tables)) {
                        $isValid = false;
                        array_push($msgs, "The field {$fld->name} in the form {$tbl->name} has the form {$fld->branch_form} set as it's branch form, but the form {$fld->branch_form} doesn not exist.");
                    }
                }
                if ($fld->regex) {
                    //make sure the REGEX is a valid Regex
                    try {
                        preg_match("/" . $fld->regex . "/", "12345");
                    } catch (Exception $err) {
                        array_push($msgs, "The field {$fld->name} in the form {$tbl->name} has an invalid regular expression in it's regex attribute \"($fld->regex)\".");
                    }
                }
            }
        }
        $name = $prj->name;
    }

    if ($returnJson) {
        return count($msgs) == 0 ? true : str_replace('"', '\"', implode("\",\"", $msgs));
    } elseif (getValIfExists($_REQUEST, "json")) {
        echo "{\"valid\" : " . (count($msgs) == 0 ? "true" : "false") . ", \"msgs\" : [ \"" . implode("\",\"", $msgs) . "\" ], \"name\" : \"$name\", \"file\" :\"$fn\" }";
    } else {
        return count($msgs) == 0 ? true : "<ol><li>" . str_replace('"', '\"', implode("</li><li>", $msgs)) . "</li></ol>";
    }
}