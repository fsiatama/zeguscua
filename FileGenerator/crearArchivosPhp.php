<?php
ini_set('display_errors', true);
error_reporting(E_ALL);

$base         = "sismar_usa"; //$_GET["db"];
$nombre_tabla = "country"; //$_GET["tabla"];
$app_name     = "Sicex";

if($base == "" || $nombre_tabla == ""){
	print "no hay datos";
	exit();
}
if(!file_exists($nombre_tabla)){
	mkdir($nombre_tabla);
}

require '../vendor/autoload.php';


$conn = &ADONewConnection('mysqli');  # crea la conexion
$conn->PConnect('172.16.1.233','root','','information_schema');# se conecta a la base de datos agora
$sql = "SELECT * FROM COLUMNS WHERE TABLE_SCHEMA = '".$base."' AND (TABLE_NAME = '".$nombre_tabla."' )";
$rs = $conn->Execute($sql);
//print $rs->GetMenu('COLUMN_NAME','orden_compra_cab_id');

/******************************************************************************************************/
/*********************************Inicia creacion del Entity**********************************************/
/******************************************************************************************************/
$result    = array();
$result2   = array();
$contenido = "";
$cabecera  = "";
$variables = "";

$cabecera  = "<?php namespace {$app_name}\Entities\\{$base};\r\n\r\nclass ".ucfirst($nombre_tabla)." {\r\n\r\n";

$llave_primaria = false;
while (!$rs->EOF) {
	$contenido  .= "	public function set". ucfirst($rs->fields["COLUMN_NAME"])."(\$". $rs->fields["COLUMN_NAME"] ."){\r\n";
	$contenido  .= "		\$this->". $rs->fields["COLUMN_NAME"]." = \$". $rs->fields["COLUMN_NAME"].";\r\n";
	$contenido  .= "	}\r\n\r\n";
	$contenido  .= "	public function get". ucfirst($rs->fields["COLUMN_NAME"])."(){\r\n";
	$contenido  .= "		return \$this->". $rs->fields["COLUMN_NAME"].";\r\n";
	$contenido  .= "	}\r\n\r\n";
	$variables  .= "	private \$". $rs->fields["COLUMN_NAME"]. " = null;\r\n";
	$result[]    = $rs->fields["COLUMN_NAME"];
	$result2[]   = $rs->fields;
	if($rs->fields["COLUMN_KEY"] == "PRI"){
		$llave_primaria = $rs->fields["COLUMN_NAME"];
	}
	$rs->MoveNext();
}

if($llave_primaria === false){
	print "no existe llave primaria";
	exit();
}
$cabecera .= $variables."\r\n";
$cabecera .= $contenido;
$cabecera .= "}";
//printf($contenido);

$archivo = $nombre_tabla . "/" . ucfirst($nombre_tabla).".php";
if(!file_exists($archivo)){
	$fp = fopen($archivo,"w+");
	fwrite($fp, $cabecera);
	fclose($fp);
}
else {
	print $archivo . " ya existe, no se ha creado \r\n";
}

/******************************************************************************************************/
/*********************************Inicia creacion del Lang**********************************************/
/******************************************************************************************************/
$contenido = "<?php
return [
	'table_name'    => '".Inflector::human($nombre_tabla)."',
	'columns_title' => [\r\n";
foreach($result as $key => $campos){
	$contenido .= "		'".$campos."' => '".Inflector::human($campos)."',\r\n";
}
$contenido .= "	]
];
";

$archivo = $nombre_tabla . "/" .($nombre_tabla)."-lang.php";
if(!file_exists($archivo)){
	$fp = fopen($archivo,"w+");
	fwrite($fp, $contenido);
	fclose($fp);
}
else {
	print $archivo . " ya existe, no se ha creado ";
}


/******************************************************************************************************/
/*********************************Inicia creacion del Ado**********************************************/
/******************************************************************************************************/
$getters = "";
foreach($result as $key => $campos){
	$getters .= "		\$" . $campos . " = \$" . $nombre_tabla ."->get".ucfirst($campos)."();\r\n";
}

$contenido = "<?php namespace {$app_name}\Ado\\{$base};

use {$app_name}\Ado\BaseAdo;

class ".ucfirst($nombre_tabla)."Ado extends BaseAdo {

	protected function setTable()
	{
		\$this->table = '".$nombre_tabla."';
	}

	protected function setPrimaryKey()
	{
		\$this->primaryKey = '".$llave_primaria."';
	}

	protected function setData()
	{
		\$".$nombre_tabla." = \$this->getModel();

".$getters."
		\$this->data = compact(
			'".implode("',\r\n			'", $result)."'
		);
	}

	public function create(\$".$nombre_tabla.")
	{
		\$conn = \$this->getConnection();
		\$this->setModel(\$".$nombre_tabla.");
		\$this->setData();

		\$sql = '
			INSERT INTO ".$nombre_tabla." (
				".implode(",\r\n				", $result)."
			)
			VALUES (
				\"'.\$this->data['".implode("'].'\",\r\n				\"'.\$this->data['", $result)."'].'\"
			)
		';
		\$resultSet = \$conn->Execute(\$sql);
		\$result = \$this->buildResult(\$resultSet, \$conn->Insert_ID());

		return \$result;
	}

	protected function buildSelect()
	{

		\$sql = 'SELECT
			 ".implode(",\r\n			 ", $result)."
			FROM ".$nombre_tabla."
		';

		\$sql .= \$this->buildSelectWhere();

		return \$sql;
	}


	private function buildSelectWhere()
	{
		\$filter         = [];
		\$primaryFilter  = [];
		\$operator       = \$this->getOperator();
		\$joinOperator   = ' AND ';
		\$selectedValues = \$this->getSelectedValues();
		\$this->setWhereAssignment( false );

		foreach(\$this->data as \$key => \$value){
			if ( ! is_null(\$value) ){
				if (\$key == '".$llave_primaria."') {
					\$primaryFilter[] = \$key . ' IN (\"' . \$value . '\")';
				} else {
					if (\$operator == '=') {
						\$filter[] = \$key . ' ' . \$operator . ' \"' . \$value . '\"';
					}
					elseif (\$operator == 'IN') {
						\$filter[] = \$key . ' ' . \$operator . '(\"' . \$value . '\")';
					}
					else {
						\$filter[] = \$key . ' ' . \$operator . ' \"%' . \$value . '%\"';
						\$joinOperator = ' OR ';
					}
				}
			}
		}

		\$sql             = '';

		if(!empty(\$primaryFilter)){
			\$sql            .= (\$this->getWhereAssignment()) ? ' AND ' : ' WHERE ' ;
			\$sql            .= ' ('. implode( ' AND ', \$primaryFilter ).')';
			\$this->setWhereAssignment( true );
		}
		if (!empty(\$selectedValues)) {

			\$sql .= (\$this->getWhereAssignment()) ? ' AND ' : ' WHERE ' ;
			\$sql .= ' ( NOT '.\$this->primaryKey.' IN ('.\$selectedValues.') ) ';
			\$this->setWhereAssignment( true );
			
		}
		if(!empty(\$filter)){
			\$sql .= (\$this->getWhereAssignment()) ? ' AND ' : ' WHERE ' ;
			\$sql .= '  ('. implode( \$joinOperator, \$filter ).')';
		}

		return \$sql;
	}

}
";

$archivo = $nombre_tabla . "/" .ucfirst($nombre_tabla)."Ado.php";
if(!file_exists($archivo)){
	$fp = fopen($archivo,"w+");
	fwrite($fp, $contenido);
	fclose($fp);
}
else {
	print $archivo . " ya existe, no se ha creado ";
}

/******************************************************************************************************/
/*********************************Inicia creacion del Repo**********************************************/
/******************************************************************************************************/

$contenido = "<?php namespace {$app_name}\Repositories\\{$base};

use Validator;
use {$app_name}\Entities\\{$base}\\".ucfirst($nombre_tabla).";
use {$app_name}\Ado\\{$base}\\".ucfirst($nombre_tabla)."Ado;
use {$app_name}\Repositories\BaseRepo;

class ".ucfirst($nombre_tabla)."Repo extends BaseRepo {

	private \$dbName = '".$base."';

	protected function getModel()
	{
		return new ".ucfirst($nombre_tabla).";
	}
	
	protected function getModelAdo()
	{
		return new ".ucfirst($nombre_tabla)."Ado(\$this->dbName);
	}

	protected function getPrimaryKey()
	{
		return '".$llave_primaria."';
	}

	public function validateModify(\$params)
	{
		extract(\$params);
		\$result = \$this->findPrimaryKey(\$".$llave_primaria.");

		if (!\$result['success']) {
			\$result = [
				'success'  => false,
				'error'    => \$result['error']
			];
		}
		return \$result;
	}

	protected function getRules()
	{
		return [\r\n";
foreach($result as $key => $campos){
	if ($campos != $llave_primaria) {
		$contenido .= "			'".$campos."' => 'required',\r\n";
	}
}
$contenido .= "
		];
	}

	protected function setData(\$params, \$action)
	{
		extract(\$params);

		if (\$action == 'modify') {
			\$result = \$this->findPrimaryKey(\$".$llave_primaria.");

			if (!\$result['success']) {
				\$result = [
					'success'  => false,
					'error'    => \$result['error']
				];
				return \$result;
			}
		}
		\$validator = new Validator('".$nombre_tabla."', \$params, \$this->rules);

		if ( ! \$validator->passes() ) {
			\$result = [
				'success' => false,
				'error'   => implode(', ', \$validator->errors())
			];
			return \$result;
		}
";
$contenido_update = "";
$contenido_insert = "";
foreach($result as $key => $campos){
	$str = substr($campos, -7);
	if ($str == 'uinsert') {
		$contenido_insert .= "			\$this->model->set".ucfirst($campos)."(\$_SESSION['session_usuario_id']);\r\n";
	} elseif ($str == 'finsert') {
		$contenido_insert .= "			\$this->model->set".ucfirst($campos)."(Helpers::getDateTimeNow());\r\n";
	} elseif ($str == 'uupdate') {
		$contenido_update .= "			\$this->model->set".ucfirst($campos)."(\$_SESSION['session_usuario_id']);\r\n";
	} elseif ($str == 'fupdate') {
		$contenido_update .= "			\$this->model->set".ucfirst($campos)."(Helpers::getDateTimeNow());\r\n";
	} else {
		if ($campos != $llave_primaria) {
			$contenido .= "		\$this->model->set".ucfirst($campos)."(\$" . $campos . ");\r\n";
		}
	}
}
$contenido .= "
		if (\$action == 'create') {
".$contenido_insert."		} elseif (\$action == 'modify') {
".$contenido_update."		}
		\$result = ['success' => true];
		return \$result;
	}

	public function listAll(\$params)
	{
		extract(\$params);
		\$query    = ( isset(\$query) ) ? \$query : null;
		\$query    = ( ! empty(\$query) ) ? \$query : null;
		\$start    = ( isset(\$start) ) ? \$start : 0;
		\$limit    = ( isset(\$limit) ) ? \$limit : MAXREGEXCEL;
		\$selected = ( isset(\$selected) ) ? \$selected : '';
		\$page     = ( \$start==0 ) ? 1 : ( \$start/\$limit )+1;

		if (!empty(\$valuesqry) && \$valuesqry) {
			\$query = explode('|',\$query);
			\$this->model->set".ucfirst($llave_primaria)."(implode('\", \"', \$query));
";
/*foreach($result as $key => $campos){
	$contenido .= "			\$this->model->set".ucfirst($campos)."(implode('\", \"', \$query));\r\n";
}*/
$contenido .= "
			return \$this->modelAdo->inSearch(\$this->model);
		} else {
";
foreach($result as $key => $campos){
	$contenido .= "			\$this->model->set".ucfirst($campos)."(\$query);\r\n";
}
$contenido .= "
			
			if (!empty(\$selected)) {
				\$this->modelAdo->setSelectedValues(\$selected);
			}

			return \$this->modelAdo->paginate(\$this->model, 'LIKE', \$limit, \$page);
		}

	}

}
";

$archivo = $nombre_tabla . "/" .ucfirst($nombre_tabla)."Repo.php";
if(!file_exists($archivo)){
	$fp = fopen($archivo,"w+");
	fwrite($fp, $contenido);
	fclose($fp);
}
else {
	print $archivo . " ya existe, no se ha creado ";
}

/******************************************************************************************************/
/*********************************Inicia creacion del Controller**********************************************/
/******************************************************************************************************/

$contenido = "<?php

use {$app_name}\Repositories\\{$base}\\".ucfirst($nombre_tabla)."Repo;

class ".ucfirst($nombre_tabla)."Controller {
	
	protected \$".($nombre_tabla)."Repo;
	protected \$usuarioRepo;

	public function __construct()
	{
		\$this->".($nombre_tabla)."Repo = new ".ucfirst($nombre_tabla)."Repo;
		\$this->usuarioRepo        = new usuarioRepo;
	}
	
	public function listAction(\$urlParams, \$postParams)
    {
        return \$this->".($nombre_tabla)."Repo->listAll(\$postParams);
    }

}
	

";

$archivo = $nombre_tabla . "/" .ucfirst($nombre_tabla)."Controller.php";
if(!file_exists($archivo)){
	$fp = fopen($archivo,"w+");
	fwrite($fp, $contenido);
	fclose($fp);
}
else {
	print $archivo . " ya existe, no se ha creado ";
}


$contenido = "";
$cabecera  = "";
$variables = "";

$contenido .= "/*<script>*/\r\n";

$arr_str_fields  = array();
$arr_col_model   = array();
$arr_form_reader = array();
$arr_form_items  = array();

foreach($result2 as $key => $campos){
	$tipo    = "string";
	$alinear = "left";
	$format  = "";
	$column_xtype = "";
	$column_format = "";
	$xtype   = "textfield";
	
	if($campos["DATA_TYPE"] == "varchar"  || $campos["DATA_TYPE"] == "text" || $campos["DATA_TYPE"] == "char"){
		$tipo = "string";
		$alinear = "left";
		$xtype   = "textfield";
	}
	elseif($campos["DATA_TYPE"] == "int" || $campos["DATA_TYPE"] == "tinyint" || $campos["DATA_TYPE"] == "mediumint" || $campos["DATA_TYPE"] == "smallint" || $campos["DATA_TYPE"] == "bigint" || $campos["DATA_TYPE"] == "double"){
		$tipo = "float";
		$alinear = "right";
		$xtype   = "numberfield";
		$column_xtype = "xtype:'numbercolumn', ";
	}
	elseif($campos["DATA_TYPE"] == "date"){
		$tipo    = "string";
		$alinear = "left";
		$xtype   = "datefield";
		$format  = ", dateFormat:'Y-m-d'";
		$column_xtype = "xtype:'datecolumn', ";
		$column_format = ", format:'Y-m-d'";
	}
	elseif($campos["DATA_TYPE"] == "datetime"){
		$tipo    = "date";
		$alinear = "left";
		$xtype   = "datefield";
		$format  = ", dateFormat:'Y-m-d H:i:s'";
		$column_xtype = "xtype:'datecolumn', ";
		$column_format = ", format:'Y-m-d, g:i a'";
	}
	
	$arr_str_fields[]  = "{name:'".$campos["COLUMN_NAME"]."', type:'".$tipo."'".$format."}";;
	$arr_col_model[]   = "{".$column_xtype."header:'<?= Lang::get('".$nombre_tabla.".columns_title.".$campos["COLUMN_NAME"]."'); ?>', align:'".$alinear ."', hidden:false, dataIndex:'".$campos["COLUMN_NAME"]."'".$column_format."}";
	$arr_form_reader[] = "{name:'".$campos["COLUMN_NAME"]."', mapping:'".$campos["COLUMN_NAME"]."', type:'".$tipo."'}";
	$str  = "defaults:{anchor:'100%'}\r\n";
	$str .= "			,items:[{\r\n";
	$str .= "				xtype:'".$xtype."'\r\n";
	$str .= "				,name:'".$campos["COLUMN_NAME"]."'\r\n";
	$str .= "				,fieldLabel:'<?= Lang::get('".$nombre_tabla.".columns_title.".$campos["COLUMN_NAME"]."'); ?>'\r\n";
	$str .= "				,id:module+'".$campos["COLUMN_NAME"]."'\r\n";
	$str .= "				,allowBlank:false\r\n";
	$str .= "			}]\r\n";
	
	$arr_form_items[] = $str;
}

$contenido .= "var store".ucfirst($nombre_tabla)." = new Ext.data.JsonStore({\r\n";
$contenido .= "	url:'".$nombre_tabla."/list'\r\n";
$contenido .= "	,root:'data'\r\n";
$contenido .= "	,sortInfo:{field:'".$llave_primaria."',direction:'ASC'}\r\n";
$contenido .= "	,totalProperty:'total'\r\n";
$contenido .= "	,baseParams:{id:'<?= \$id; ?>'}\r\n";
$contenido .= "	,fields:[\r\n";
$contenido .= "		".implode(",\r\n		",$arr_str_fields)."\r\n";
$contenido .= "	]\r\n";
$contenido .= "});\r\n";


$contenido .= "var combo".ucfirst($nombre_tabla)." = new Ext.form.ComboBox({\r\n";
$contenido .= "	hiddenName:'".$nombre_tabla."'\r\n";
$contenido .= "	,id:module+'combo".ucfirst($nombre_tabla)."'\r\n";
$contenido .= "	,fieldLabel:'<?= Lang::get('".$nombre_tabla.".columns_title.".$campos["COLUMN_NAME"]."'); ?>'\r\n";
$contenido .= "	,store:store".ucfirst($nombre_tabla)."\r\n";
$contenido .= "	,valueField:'".$llave_primaria."'\r\n";
$contenido .= "	,displayField:'".$nombre_tabla."_name'\r\n";
$contenido .= "	,typeAhead:true\r\n";
$contenido .= "	,forceSelection:true\r\n";
$contenido .= "	,triggerAction:'all'\r\n";
$contenido .= "	,selectOnFocus:true\r\n";
$contenido .= "	,allowBlank:false\r\n";
$contenido .= "	,listeners:{\r\n";
$contenido .= "		select: {\r\n";
$contenido .= "			fn: function(combo,reg){\r\n";
$contenido .= "				Ext.getCmp(module + '".$llave_primaria."').setValue(reg.data.".$llave_primaria.");\r\n";
$contenido .= "			}\r\n";
$contenido .= "		}\r\n";
$contenido .= "	}\r\n";
$contenido .= "});\r\n";

$contenido .= "var cm".ucfirst($nombre_tabla)." = new Ext.grid.ColumnModel({\r\n";
$contenido .= "	columns:[\r\n";
$contenido .= "		".implode(",\r\n		",$arr_col_model)."\r\n";
$contenido .= "	]\r\n";
$contenido .= "	,defaults:{\r\n";
$contenido .= "		sortable:true\r\n";
$contenido .= "		,width:100\r\n";
$contenido .= "	}\r\n";
$contenido .= "});\r\n";

$contenido .= "var tb".ucfirst($nombre_tabla)." = new Ext.Toolbar();\r\n\r\n";

$contenido .= "var grid".ucfirst($nombre_tabla)." = new Ext.grid.GridPanel({\r\n";
$contenido .= "	store:store".ucfirst($nombre_tabla)."\r\n";
$contenido .= "	,id:module+'grid".ucfirst($nombre_tabla)."'\r\n";
$contenido .= "	,colModel:cm".ucfirst($nombre_tabla)."\r\n";
$contenido .= "	,viewConfig: {\r\n";
$contenido .= "		forceFit: true\r\n";
$contenido .= "		,scrollOffset:2\r\n";
$contenido .= "	}\r\n";
$contenido .= "	,sm:new Ext.grid.RowSelectionModel({singleSelect:true})\r\n";
$contenido .= "	,bbar:new Ext.PagingToolbar({pageSize:10, store:store".ucfirst($nombre_tabla).", displayInfo:true})\r\n";
$contenido .= "	,tbar:tb".ucfirst($nombre_tabla)."\r\n";
$contenido .= "	,loadMask:true\r\n";
$contenido .= "	,border:false\r\n";
$contenido .= "	,title:''\r\n";
$contenido .= "	,iconCls:'icon-grid'\r\n";
$contenido .= "	,plugins:[new Ext.ux.grid.Excel()]\r\n";
$contenido .= "});\r\n";

$contenido .= "var form".ucfirst($nombre_tabla)." = new Ext.FormPanel({\r\n";
$contenido .= "	baseCls:'x-panel-mc'\r\n";
$contenido .= "	,method:'POST'\r\n";
$contenido .= "	,baseParams:{accion:'act'}\r\n";
$contenido .= "	,autoWidth:true\r\n";
$contenido .= "	,autoScroll:true\r\n";
$contenido .= "	,trackResetOnLoad:true\r\n";
$contenido .= "	,monitorValid:true\r\n";
$contenido .= "	,bodyStyle:'padding:15px;'\r\n";
$contenido .= "	,reader: new Ext.data.JsonReader({\r\n";
$contenido .= "		root:'data'\r\n";
$contenido .= "		,totalProperty:'total'\r\n";
$contenido .= "		,fields:[\r\n";
$contenido .= "			".implode(",\r\n			",$arr_form_reader)."\r\n";
$contenido .= "		]\r\n";
$contenido .= "	})\r\n";
$contenido .= "	,items:[{\r\n";
$contenido .= "		xtype:'fieldset'\r\n";
$contenido .= "		,title:'Information'\r\n";
$contenido .= "		,layout:'column'\r\n";
$contenido .= "		,defaults:{\r\n";
$contenido .= "			columnWidth:0.33\r\n";
$contenido .= "			,layout:'form'\r\n";
$contenido .= "			,labelAlign:'top'\r\n";
$contenido .= "			,border:false\r\n";
$contenido .= "			,xtype:'panel'\r\n";
$contenido .= "			,bodyStyle:'padding:0 18px 0 0'\r\n";
$contenido .= "		}\r\n";
$contenido .= "		,items:[{\r\n";
$contenido .= "			".implode("		},{\r\n			",$arr_form_items)."";
$contenido .= "		}]\r\n";
$contenido .= "	}]\r\n";
$contenido .= "});\r\n";


$archivo = $nombre_tabla . "/" . $nombre_tabla."_store.js.php";

if(!file_exists($archivo)){
	$fp = fopen($archivo,"w+");
	fwrite($fp, $contenido);
	fclose($fp);
}
else {
	print $archivo . " ya existe, no se ha creado ";
}


print " termino";
?>
