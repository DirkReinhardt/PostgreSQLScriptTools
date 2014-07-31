<?php
$Config = parse_ini_file(dirname(__file__).'/config.ini', true);

if (empty($Config['DB1']['port'])) $Config['DB1']['port'] = '5432';
if (empty($Config['DB2']['port'])) $Config['DB2']['port'] = '5432';

define('MOr', '<span class="MRed">');
define('MOg', '<span class="MGreen">');
define('MOy', '<span class="MYellow">');
define('MOgr', '<span class="MGray">');
define('ME', '</span>');

function compareDBschemes($Schema_A, $Schema_B) {
	$CompareData = array(
		'Exists'	=> array('A'=>true,'B'=>true),
		'Tables' 	=> array(),
	);

	//Validierung Schema
	if (!isset($Schema_A) || (!is_array($Schema_A))) {
		$CompareData['Exists']['A'] = false;
		$Schema_A = array(
			'tables'	=> array(),
		);
	}
	if (!isset($Schema_B) || (!is_array($Schema_B))) {
		$CompareData['Exists']['B'] = false;
		$Schema_B = array(
			'tables'	=> array(),
		);
	}

	foreach ($Schema_A['tables'] AS $TableNameIndex => $TableData_A) {
		$CompareData['Tables'][$TableNameIndex] = array(
			'Exists'	=> array('A'=>true,'B'=>true),
			'Fields' 	=> array(),
		);

		if (!isset($Schema_B['tables'][$TableNameIndex]) || (!is_array($Schema_B['tables'][$TableNameIndex]))) {
			$CompareData['Tables'][$TableNameIndex]['Exists']['B'] = false;
			//echo $TableNameIndex.'<br>';
			continue;//Weiter nur wenn fehlende Tabellen angezeigt werden sollen
			$TableData_B = array('columns'=>array());
		}
		else {
			$TableData_B = $Schema_B['tables'][$TableNameIndex];
		}

		// Tabelle in beiden Schemas da, Felder vergleichen
		foreach ($TableData_A['columns'] AS $ColumnNameIndex => $ColumnData_A) {

			if (!isset($TableData_B['columns'][$ColumnNameIndex])) {

				$FieldStr = $ColumnData_A['data_type'];
				if ($ColumnData_A['is_nullable'] != 'YES') $FieldStr .= ' NOT NULL';
				if ($ColumnData_A['column_default'] != '') $FieldStr .= ' DEFAULT '.$ColumnData_A['column_default'];

				$CompareData['Tables'][$TableNameIndex]['Fields'][$ColumnNameIndex]['A'] = MOgr.'"'.$ColumnNameIndex.'" '.$FieldStr.ME;
				$CompareData['Tables'][$TableNameIndex]['Fields'][$ColumnNameIndex]['B'] = '';
			}
			else {
				$ColumnData_B = $TableData_B['columns'][$ColumnNameIndex];
				$IsDiff = false;
				//Datentyp
				if ($ColumnData_A['data_type'] != $ColumnData_B['data_type']){
					$FieldStrA = MOr.$ColumnData_A['data_type'].ME;
					$FieldStrB = MOr.$ColumnData_B['data_type'].ME;
					$IsDiff = true;
				} else {
					$FieldStrA = $ColumnData_A['data_type'];
					$FieldStrB = $ColumnData_B['data_type'];
				}
				//NotNull
				if ($ColumnData_A['is_nullable'] != $ColumnData_B['is_nullable']){
					$FieldStrA .= MOg.(($ColumnData_A['is_nullable']=='YES')?'':' NOT NULL').ME;
					$FieldStrB .= MOg.(($ColumnData_B['is_nullable']=='YES')?'':' NOT NULL').ME;
					$IsDiff = true;
				} else {
					$FieldStrA .= (($ColumnData_A['is_nullable']=='YES')?'':' NOT NULL');
					$FieldStrB .= (($ColumnData_B['is_nullable']=='YES')?'':' NOT NULL');
				}
				//DefaultValue
				$OnlyVersionDiff = false;
				$ModdedName_A = str_replace('::text)::regclass)', '::regclass)', str_replace('nextval((', 'nextval(', $ColumnData_A['column_default']));
				$ModdedName_B = str_replace('::text)::regclass)', '::regclass)', str_replace('nextval((', 'nextval(', $ColumnData_B['column_default']));
				if (strpos($ModdedName_A, '"') === false) $ModdedName_A = strtolower($ModdedName_A);
				if (strpos($ModdedName_B, '"') === false) $ModdedName_B = strtolower($ModdedName_B);
				$OnlyVersionDiff = ($ModdedName_A == $ModdedName_B);
				if (($ColumnData_A['column_default'] !== $ColumnData_B['column_default']) AND (!$OnlyVersionDiff)){
					$FieldStrA .= MOy.(((string)$ColumnData_A['column_default'] != '')?(' DEFAULT '.$ColumnData_A['column_default']):'').ME;
					$FieldStrB .= MOy.(((string)$ColumnData_B['column_default'] != '')?(' DEFAULT '.$ColumnData_B['column_default']):'').ME;
					//echo $ColumnNameIndex.' : ';var_dump($ColumnData_A['column_default']);var_dump($ColumnData_B['column_default']);
					$IsDiff = true;
				} else {
					$FieldStrA .= (((string)$ColumnData_A['column_default'] != '')?(' DEFAULT '.$ColumnData_A['column_default']):'');
					$FieldStrB .= (((string)$ColumnData_B['column_default'] != '')?(' DEFAULT '.$ColumnData_B['column_default']):'');
				}
				//Setzen
				if ($IsDiff) {
					$CompareData['Tables'][$TableNameIndex]['Fields'][$ColumnNameIndex]['A'] = '"'.$ColumnNameIndex.'" '.$FieldStrA;
					$CompareData['Tables'][$TableNameIndex]['Fields'][$ColumnNameIndex]['B'] = '"'.$ColumnNameIndex.'" '.$FieldStrB;
				}
				else{
					$CompareData['Tables'][$TableNameIndex]['Fields'][$ColumnNameIndex] = false;
				}
			}
		}
	}

	foreach ($Schema_B['tables'] AS $TableNameIndex => $TableData_B) {
		if (!isset($CompareData['Tables'][$TableNameIndex])) {
			$CompareData['Tables'][$TableNameIndex] = array(
				'Exists'	=> array('A'=>false,'B'=>true),
				'Fields' 	=> array(),
			);
		}
		foreach ($TableData_B['columns'] AS $ColumnNameIndex => $ColumnData_B) {
			if (!array_key_exists($ColumnNameIndex, $CompareData['Tables'][$TableNameIndex]['Fields'])) {

				$FieldStr = $ColumnData_B['data_type'];
				if ($ColumnData_B['is_nullable']!='YES') $FieldStr .= ' NOT NULL';
				if ($ColumnData_B['column_default'] != '') $FieldStr .= ' DEFAULT '.$ColumnData_B['column_default'];

				$CompareData['Tables'][$TableNameIndex]['Fields'][$ColumnNameIndex]['A'] = '';
				$CompareData['Tables'][$TableNameIndex]['Fields'][$ColumnNameIndex]['B'] = MOgr.'"'.$ColumnNameIndex.'" '.$FieldStr.ME;
			}
		}
	}

	return $CompareData;
}

$DBLayout1=$DBLayout2=array();

$DBCon1 = @pg_connect ("host={$Config['DB1']['host']} dbname={$Config['DB1']['name']} user={$Config['DB1']['user']} password={$Config['DB1']['pass']} port={$Config['DB1']['port']}");
if (!$DBCon1) {
	echo "Could not connect to Database 1<br>\n";
	exit;
}

$DBCon2 = @pg_connect ("host={$Config['DB2']['host']} dbname={$Config['DB2']['name']} user={$Config['DB2']['user']} password={$Config['DB2']['pass']} port={$Config['DB2']['port']}");
if (!$DBCon2) {
	echo "Could not connect to Database 2<br>\n";
	exit;
}

// einlesen der DB-Layouts
$SQLQuery = 'SELECT "table_schema" , "table_name" FROM "information_schema"."tables" ';
$SQLResult = pg_query($DBCon1,$SQLQuery);
$DBLayout1["schemes"]=array();
if ($SQLResult) {
	while (list($tmp_table_schema,$tmp_table_name)=pg_fetch_row($SQLResult)) {
		if ($tmp_table_schema == 'information_schema') continue;
		if ($tmp_table_schema == 'pg_catalog') continue;
		if ((isset($Config['DB1']['scheme'])) && ($Config['DB1']['scheme'] != '')) {
			if ($tmp_table_schema != $Config['DB1']['scheme']) continue;
		}

		if (!isset($DBLayout1["schemes"][$tmp_table_schema])) $DBLayout1["schemes"][$tmp_table_schema]=array();

		$DBLayout1["schemes"][$tmp_table_schema]['tables'][$tmp_table_name]=array('columns'=> array(),'privs' => array());
	}
	pg_free_result($SQLResult);
}
//print_r($DBLayout1);
$SQLQuery = 'SELECT "table_schema" , "table_name" , "column_name" , "ordinal_position" ,
					"column_default" , "is_nullable" , "data_type" , "character_maximum_length"
			 FROM "information_schema"."columns" ';
$SQLResult = pg_query($DBCon1,$SQLQuery);
if ($SQLResult) {
	while (list($tmp_table_schema,$tmp_table_name,$tmp_column_name,$tmp_ordinal_position,
				$tmp_column_default,$tmp_is_nullable,$tmp_data_type,$tmp_character_maximum_length)=pg_fetch_row($SQLResult)) {
		if ($tmp_table_schema == 'information_schema') continue;
		if ($tmp_table_schema == 'pg_catalog') continue;

		if (!isset($DBLayout1["schemes"][$tmp_table_schema])) continue;
		if (!isset($DBLayout1["schemes"][$tmp_table_schema]['tables'][$tmp_table_name])) continue;

		$tmp_column=array(	"column_name"		=> $tmp_column_name ,
							"ordinal_position"	=> $tmp_ordinal_position,
							"data_type"			=> $tmp_data_type,
							"character_maximum_length"	=> $tmp_character_maximum_length,
							"column_default"	=> $tmp_column_default,
							"is_nullable"		=> $tmp_is_nullable
						 );
		$DBLayout1["schemes"][$tmp_table_schema]['tables'][$tmp_table_name]['columns'][$tmp_column_name]=$tmp_column;
	}
	pg_free_result($SQLResult);
}

pg_close($DBCon1);

// einlesen der DB-Layouts
$SQLQuery = 'SELECT "table_schema" , "table_name" FROM "information_schema"."tables" ';
$SQLResult = pg_query($DBCon2,$SQLQuery);
$DBLayout2["schemes"]=array();
if ($SQLResult) {
	while (list($tmp_table_schema,$tmp_table_name)=pg_fetch_row($SQLResult)) {
		if ($tmp_table_schema == 'information_schema') continue;
		if ($tmp_table_schema == 'pg_catalog') continue;

		if ((isset($Config['DB2']['scheme'])) && ($Config['DB2']['scheme'] != '')) {
			if ($tmp_table_schema != $Config['DB2']['scheme']) continue;
		}

		if (!isset($DBLayout2["schemes"][$tmp_table_schema])) $DBLayout2["schemes"][$tmp_table_schema]=array();

		$DBLayout2["schemes"][$tmp_table_schema]['tables'][$tmp_table_name]=array('columns'=> array(),'privs' => array());
	}
	pg_free_result($SQLResult);
}

$SQLQuery = 'SELECT "table_schema" , "table_name" , "column_name" , "ordinal_position" ,
					"column_default" , "is_nullable" , "data_type" , "character_maximum_length"
			 FROM "information_schema"."columns" ';
$SQLResult = pg_query($DBCon2,$SQLQuery);
if ($SQLResult) {
	while (list($tmp_table_schema,$tmp_table_name,$tmp_column_name,$tmp_ordinal_position,
				$tmp_column_default,$tmp_is_nullable,$tmp_data_type,$tmp_character_maximum_length)=pg_fetch_row($SQLResult)) {
		if ($tmp_table_schema == 'information_schema') continue;
		if ($tmp_table_schema == 'pg_catalog') continue;

		if (!isset($DBLayout2["schemes"][$tmp_table_schema])) continue;
		if (!isset($DBLayout2["schemes"][$tmp_table_schema]['tables'][$tmp_table_name])) continue;

		$tmp_column=array(	"column_name"		=> $tmp_column_name ,
							"ordinal_position"	=> $tmp_ordinal_position,
							"data_type"			=> $tmp_data_type,
							"character_maximum_length"	=> $tmp_character_maximum_length,
							"column_default"	=> $tmp_column_default,
							"is_nullable"		=> $tmp_is_nullable
						 );
		$DBLayout2["schemes"][$tmp_table_schema]['tables'][$tmp_table_name]['columns'][$tmp_column_name]=$tmp_column;
	}
	pg_free_result($SQLResult);
}

pg_close($DBCon2);

//var_dump($DBLayout1);

foreach ($DBLayout1["schemes"] AS $SchemeName1 => $tmp_Scheme1) {
	if (!isset($DBLayout2["schemes"][$SchemeName1])) {
		echo "scheme \"$SchemeName1\" does not exist in DB2<br>\n";continue;
	}

	$compareresult=compareDBschemes($DBLayout1["schemes"][$SchemeName1],$DBLayout2["schemes"][$SchemeName1]);
	//echo'<pre>'.print_r($compareresult['Tables'],true).'</pre>';
	echo'
		<style type="text/css">
			.MGray { font-weight:bold; color:black; background-color:#D7D7D7; }
			.MRed { font-weight:bold; color:black; background-color:#C86464; }
			.MGreen { font-weight:bold; color:black; background-color:#64C864; }
			.MYellow { font-weight:bold; color:black; background-color:#E1E100; }
			table { border-collapse:collapse;border-style:solid;border-width:2px; }
			.LH { border-style:solid;border-width:2px;font-weight:bold;background-color:#C0C0C0; }
			td { border-style:dotted;border-width:1px; }
		</style>
	';
	echo '<table>';
	$TabNameList = array_keys($compareresult['Tables']);
	natsort($TabNameList);
	foreach ($TabNameList AS $TabName) {
		$TabData = $compareresult['Tables'][$TabName];
		if (($TabData['Exists']['A']) AND ($TabData['Exists']['B'])) {
			$DoView = false;
			$TabViewStr = '<tr><td colspan="2" class="LH">"'.$SchemeName1.'"."'.$TabName.'"</td></tr>'."\n";

			foreach($TabData['Fields'] AS $ColName => $ColData){
				if (is_array($ColData)) {
					if (empty($ColData['A'])) $ColStrA = '&nbsp;'; else $ColStrA = $ColData['A'];
					if (empty($ColData['B'])) $ColStrB = '&nbsp;'; else $ColStrB = $ColData['B'];
					$TabViewStr .= '<tr><td>'.$ColStrA.'</td><td>'.$ColStrB.'</td></tr>'."\n";
					$DoView = true;
				}
			}
			if ($DoView) echo $TabViewStr.'<tr><td colspan="2">&nbsp;</td></tr>'."\n";
		}
		else {
			echo
			'<tr>'.
				'<td class="LH">'.(($TabData['Exists']['A'])?('"'.$SchemeName1.'"."'.$TabName.'"'):('- MISSING -')).'</td>'.
				'<td class="LH">'.(($TabData['Exists']['B'])?('"'.$SchemeName1.'"."'.$TabName.'"'):('- MISSING -')).'</td>'.
			'</tr>'."\n";
		}
	}
	echo '</table>';

	unset($DBLayout1["schemes"][$SchemeName1]);
	unset($DBLayout2["schemes"][$SchemeName1]);
}

foreach ($DBLayout2["schemes"] AS $SchemeName2 => $tmp_Scheme2) {
	echo "scheme \"$SchemeName2\" does not exist in DB1<br>\n";
}
?>