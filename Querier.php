<?php
	/*
	 *	This Querier class is to present a data type in php 
	 *	that makes relating with the database a lot easier and 
	 *	would not have the user remember all the syntax of mysql.
	 *
	 *	In short, this is the modelling part of my MVC.
	 *
	 *	Funso Popoola @ 2014
	*/
	class Querier{

		var $dbHost;
		var $dbPass;
		var $dbUser;
		var $link;

		var $MAX = 1000;

		function __construct($dbPass = "", $dbHost = "localhost", $dbUser = "root"){
			/*
			* The Class constructor; initializes the fields of the class
			*/
			$this->dbHost = $dbHost;
			$this->dbUser = $dbUser;
			$this->dbPass = $dbPass;
			$this->dbConnect();
		}

		function dbConnect(){
			/*
			*	This function establishes connection to mySql server;
			*	meant to be called by the constructor during initialization.
			*/
			$link = mysqli_connect($this->dbHost, $this->dbUser, $this->dbPass);
			if($link){
				$this->link = $link;
			}
			else{
				$message = "Connection to Database unsuccessful";
				die(mysql_error()."\t".$message);
			}
		}

		function dbSelect($dbName){
            /*
             * This function selects a particular database for use
             */
			$res = mysqli_select_db($this->link, $dbName);
			if(!$res){
				$errMsg = "Error while connecting to database";
				die(mysql_error()."\t".$errMsg);
			}
		}

		function select($tableName, $conditions = 1, $fields = "*", $offset = 0, $limit = "0, 1000"){
            /*
             *
             */
            $query = "SELECT ";

            //if the fields to be selected is given in an array
            if(is_array($fields)){
                $length = count($fields);
                for($i = 0; $i < $length; $i++){
                    $query .= "`".$fields[$i]."`";
                    if($i != $length - 1){
                        $query .= ", ";
                    }
                }
            }
            else{
                //if there is only one field to select or the list of fields are already provided as a string
                if($fields == "*"){
                    $query .= $fields;
                }
                else
                    $query .= "`".$fields."`";

            }

            //now, the tableName(s)
            $query .= " FROM ";

            //if the tableName is given as an array; may contain one or more tables
            if(is_array($tableName)){
                $length = count($tableName);
                for($j = 0; $j < $length; $j++){
                    $query .= "`".$tableName[$j]."`";
                    if($j != $length - 1){
                        $query .= ", ";
                    }
                }
            }
            else{
                //in case there is only one tableName given or the tableNames are given as a string
                $query .= "`".$tableName."`";
            }

            //including the WHERE clause, if there be any.
            $query .= " WHERE ".$conditions;

            //including the OFFSET
            //$query .= " OFFSET ".$offset;

            //including the LIMIT
            $query .= " LIMIT ".$limit;

            //pass the query to mysql

            $selectionRes = mysqli_query($this->link, $query);
            if(!$selectionRes){
                $errMsg = "\nSelection from database unsuccessful";
                die(mysql_error().$errMsg);
            }
            else{
                // reformat the result returned by mysql into an array of associative array;
                // even if the result is just one
                $result = array(array());
                $count = 0;
                while($row = mysqli_fetch_assoc($selectionRes)){

                    foreach($row as $key => $value){
                        $result[$count][$key] = $value;
                    }
                    $count++;
                }

                return $result;
            }
		}

		function insertInto($tableName, $fields, $values){
            /*
             *
             */
            $query = "INSERT INTO ";

            //include the table to insert into
            $query .= "`".$tableName."` ( ";

            //include the fields of the table to insert into

            if(is_array($fields)){
                $length = count($fields);
                for($i = 0; $i < $length; $i++){
                    $query .= "`".$fields[$i]."`";
                    if($i != $length - 1){
                        $query .= ", ";
                    }
                }
            }
            else{
                //if the fields is just one or written together as a string
                $query .= "`".$fields."`";

            }

            $query .= " ) VALUES ( ";

            if(is_array($values)){
                $length = count($values);
                for($i = 0; $i < $length; $i++){
                    if(is_string($values[$i]))
                        $query .= "'".$values[$i]."'";
                    else{
                        $query .= $values[$i];
                    }
                    if($i != $length - 1){
                        $query .= ", ";
                    }
                }
            }
            else{

                $query .= $values;
            }

            //end the query
            $query .= " )";

            //echo($query);

            $insertionRes = mysqli_query($this->link, $query);
            if(!$insertionRes){
                $errMsg = "Insertion into the database unsuccessful";
                die(mysql_error(). $errMsg);
            }
		}

		function update($tableName, $fields, $updates, $conditions = 1){
            /*
             * Updates a particular given table;
             * updates apply to specified fields that satisfies the given condition(s)
             */
            $query = "UPDATE "."`".$tableName."` SET ";

            //include the field = value pairs
            if(is_array($fields) and is_array($updates) and (count($fields) == count($updates))){
                $length = count($fields);
                for($i = 0; $i < $length; $i++){
                    if(is_string($updates[$i]))
                        $updates[$i] = "'".$updates[$i]."'";
                    $query .= "`".$fields[$i]."`=".$updates[$i];
                    if($i != $length - 1){
                        $query .= ", ";
                    }
                }
            }
            else{
                //only one field = value pair
                $query .= "`".$fields."`=".$updates;
            }

            //include the conditions
            $query .= " WHERE ".$conditions;

            echo($query);

            $updateRes = mysqli_query($this->link, $query);
            if(!$updateRes){
                $errMsg = "Update unsuccessful";
                die(mysql_error().$errMsg);
            }
            return $updateRes;

		}

		function createTable($newTableName, $fields, $fieldTypes, $primaryKeyFields = null){
            /*
             * Creates a table with the given tableName, fields specification and makes assigns primary key.
             *
             */
            $query = "CREATE TABLE ".$newTableName." (";

            // include the specification which is each table alongside it's description

            if(is_array($fields) and is_array($fieldTypes)){
                //just double-checking before this operation to ensure the expected types are just right

                $length = count($fields);
                if(count($fields) == count($fieldTypes)){

                    for($j = 0; $j < $length; $j++){
                        $query .= "`".$fields[$j]."` ".$fieldTypes[$j];
                        if($j != $length - 1 or !is_null($primaryKeyFields)){
                            $query .= ", ";
                        }

                    }


                    if(!is_null($primaryKeyFields)){
                        $query .= " PRIMARY KEY ( ";

                        if(is_array($primaryKeyFields)){
                            $length = count($primaryKeyFields);

                            for($k = 0; $k < $length; $k++){
                                $query .= "`".$primaryKeyFields[$j]."`";
                                if($k != $length - 1){
                                    $query .= ", ";
                                }
                            }
                        }
                        else{
                            $query .= "`".$primaryKeyFields."`";

                        }
                        $query .= " )";
                    }
                    $query .= " )";

                    //testing...
                    //echo($query);

                    //do the query
                    $creationRes = mysqli_query($this->link, $query);
                    if(!$creationRes){
                        $errMsg = " Table creation unsuccessful";
                        die(mysql_error().$errMsg);
                    }
                    return $creationRes;
                }
            }
		}

        function alterTable(){
            /*
             * Way coming...
             */
        }

		function deleteRecord($tableName, $conditions){
            /*
             *
             */
            $query = "DELETE FROM `".$tableName."` WHERE ".$conditions;

            $deletionRes = mysqli_query($this->link, $query);
            if(!$deletionRes){
                $errMsg = "\t Could not delete record(s) successfully";
                die(mysql_error().$errMsg);
            }
            return $deletionRes;
		}

		function deleteTable($tableName){
            /*
             *This is considered delicate, hence, not yet implemented
             */
            return false;
		}


		}
		
		

?>