<?php

	Class MongoModel
	{
		public $engine;
		public $db;
		public $collection;

		private $last_db = "";
		private $last_collection = "";

		private $wheres = array();
		private $orders = array();

		function __construct($crow = false,$database = false) {
			if ($crow) {
				$this->Connect($crow);
			}
			if ($database) {
				$this->DB_Connect($database);
			}
		}

		function Connect($crow) {
			$this->engine = new MongoClient($cown,['username' => MONGODB_USERNAME, 'password' => MONGODB_PASSWORD ]);
			return $this;
		}


		/*
			DATABASE
		*/
		function Database_Connect($db) {
			$this->db = $this->engine->selectDB($db);
			$this->last_db = $db;
			return $this;
		}
		function Database_Create($db) {
			$this->Database_Connect($db);
			return $this;
		}
		function Database_List($f = false) {
			$list = $this->engine->listDBs();
			if($f){
				return $list;
			}else{
				$l = array();
				foreach($list["databases"] as $database){
					$l[] = $database["name"];
				}
				return $l;
			}
		}
		function Database_Rename($db, $db_) {
			$ld = $this->last_db;
			$this->Database_Copy($db,$db_);
			$this->Database_Remove($db);
			if($ld == $db){
				$this->Database_Connect($db_);
			}
			return $this;
		}
		function Database_Remove($db) {
			$ld = $this->last_db;
			$this->Database_Connect($db);
			$this->db->command(array(
				'dropDatabase'   => 1
			));
			$this->Database_Connect($ld);
			return $this;
		}
		function Database_Copy($db,$db_) {
			$ld = $this->last_db;
			$this->Database_Connect("admin");
			$this->db->command(array(
				'copydb'   => 1,
				'fromhost' => 'localhost',
				'fromdb'   => $db,
				'todb'     => $db_
			));
			$this->Database_Connect($ld);
			return $this;
		}


		/*
			COLLECTION
		*/
		function Collection_Connect($c){
			$this->collection = new MongoCollection($this->db, $c);
			$this->last_collection = $c;
			return $this;
		}
		function Collection_Create($c , $o){
			$this->db->createCollection($c,$o);
			return $this;
		}
		function Collection_Remove($c){
			$this->Collection_Connect($c);
			$this->collection->drop();
			return $this;
		}
		function Collection_Clear($c){
			$this->Remove($c,array());
			return $this;
		}
		function Collection_List(){
			return $this->db->getCollectionNames();
		}

		/*
			COMMANDS
		*/
		function Execute($e){
			return $this->db->execute($e);
		}
		function Command($c,$data){
			//$this->Collection_Connect($c);
			//$this->collection->insert($data);
			return $this;
		}
		function Save(){
			$this->collection->save();
			return $this;
		}
		function Select($c){
			$this->Collection_Connect($c);
			$q = iterator_to_array($this->collection->find($this->wheres)->sort($this->orders));
			$this->ResetAll();
			return $q;
		}
		function Insert($c,$data){
			$this->Collection_Connect($c);
			$this->collection->insert($data);
			return $this;
		}
		function Update($c,$data,$prop = array()){
			$this->Collection_Connect($c);
			$this->collection->update($this->wheres,$data,$prop);
			$this->ResetAll();
			return $this;
		}
		function Remove($c){
			$this->Collection_Connect($c);
			$this->collection->remove($this->wheres);
			$this->ResetAll();
			return $this;
		}

		function Where($param){
			$this->wheres = $param;
			return $this;
		}
		function Order($param){
			$this->orders = $param;
			return $this;
		}
		function ResetAll(){
			$this->wheres = $this->orders = array();
			return $this;
		}

		function LastIndexId($c){
			$q = $this->Order(array(
				"_id" => -1
			))->Select($c);
			return (int)@array_keys($q)[0];
		}

	}
