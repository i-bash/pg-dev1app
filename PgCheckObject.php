<?php
class PgCheckObject{
	private static $objectChecks=[
		"getAuthors"=>[
			'type'=>'relation',
			'name'=>'authors_v',
			'message'=>'Authors view will be created in lecture 7, "Application Schema and Interface"'
		],
		"addAuthor"=>[
			'type'=>'function',
			'name'=>'add_author',
			'arguments'=>['text','text','text'],
			'message'=>'Function for adding an author will be created in lecture 12, "Executing Queries"'
		],
		"getBooks"=>[
			'type'=>'relation',
			'name'=>'catalog_v',
			'message'=>'Books view will be created in lecture 7, "Application Schema and Interface"'
		],
		"getOperations"=>[
			'type'=>'relation',
			'name'=>'operations_v',
			'message'=>'Operations view will be created in lecture 7, "Application Schema and Interface"'
		],
		"addBook"=>[
			'type'=>'function',
			'name'=>'add_book',
			'arguments'=>['text','integer[]'],
			'message'=>'Function for adding a book will be created in lecture 15, "Arrays"'
		],
		"orderBook"=>[
			'type'=>'trigger',
			'relation'=>'catalog_v',
			'tgtype'=>81, //instead of update
			'message'=>'Trigger for updating the view will be created in lecture 17, "Triggers"'
		],
		"findBooks"=>[
			'type'=>'function',
			'name'=>'get_catalog',
			'arguments'=>['text','text','boolean'],
			'message'=>'Function for searching books will be created in lecture 10, "Composite Types"'
		],
		"buyBook"=>[
			'type'=>'function',
			'name'=>'buy_book',
			'arguments'=>['integer'],
			'message'=>'Function for buying books will be created in lecture 12, "Executing Queries"'
		],
	];
	private static $columnChecks=[
		"getBooks"=>[
			'column'=>'onhand_qty',
			'message'=>'Column for on-hand quantity will be added in lecture 10, "Composite Types"'
		],
		"findBooks"=>[
			'column'=>'onhand_qty',
			'message'=>'Column for on-hand quantity will be added in lecture 10, "Composite Types"'
		],
	];
	
	private $pg; //db connection
	private $objectCheck; //current object check
	private $columnCheck; //current column check
	
	function __construct(Pg $pg, String $action){
		$this->pg=$pg;
		$this->objectCheck=self::$objectChecks[$action]??null;
		$this->columnCheck=self::$columnChecks[$action]??null;
	}
	
	static function create(Pg $pg, String $action){
		return new self($pg,$action);
	}
	
	/**
	 * @param name - relation name
	 */
	public function checkRelationExistence($name){
		try{
			$res=$this->pg->query(
				"select pg_catalog.pg_table_is_visible($1::regclass) ok",
				[$name],
				false
			);
			return $res->rows[0]->ok=='t';
		}
		catch(PgException $e){
			return false;
		}
	}
	/**
	 * @param name - function name
	 */
	public function checkFunctionExistence($name, Array $parTypes=[]){
		try{
			$res=$this->pg->query(
				"select pg_catalog.pg_function_is_visible($1::regprocedure) ok",
				[$name."(".implode(',',$parTypes).")"],
				false
			);
			return $res->rows[0]->ok=='t';
		}
		catch(PgException $e){
			return false;
		}
	}
	/**
	 * @param relation - relation name
	 * @param type - trigger type, see pg_trigger.tgtype
	 */
	public function checkTriggerExistence(string $relation,int $tgtype){
		try{
			$res=$this->pg->query(
				"select 't'
				from pg_trigger
				where tgrelid=$1::regclass
				  and tgtype=$2
				  and tgattr::text=''
				",
				[$relation,$tgtype],
				false
			);
			return count($res->rows)==1;
		}
		catch(PgException $e){
			return false;
		}
	}
	/** check if object exists
	 * @return message if object does not exist, '' otherwise
	 */
	public function checkObject(){
		if($this->objectCheck){
			$check=(object)$this->objectCheck;
			$exists=true;
			switch($check->type){
				case 'function':
					$exists=$this->checkFunctionExistence($check->name,$check->arguments??[]);
					break;
				case 'trigger':
					$exists=$this->checkTriggerExistence($check->relation,$check->tgtype);
					break;
				case 'relation':
					$exists=$this->checkRelationExistence($check->name);
					break;
			}
			return $exists?'':$check->message;
		}
		return '';
	}
	/** check if column exists
	 * @param data (object with 'columns' property)
	 * @return message if column does not exist, empty string otherwise
	 */
	public function checkColumn($data){
		return
			$data && $this->columnCheck && !in_array($this->columnCheck['column'],$data->columns)
			?$this->columnCheck['message']
			:''
		;
	}
}
