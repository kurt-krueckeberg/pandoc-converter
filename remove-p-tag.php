<?php
declare(strict_types=1);

use \SplFileObject as File;

include "FileReader.php";

/*
 BUG: Table cells that begin on a line with '^<td...><p>...' sometimes continue for one or more lines. In such cases, when the ending '</td>' is abstent,
 the regex fails. 

 If a table cell's contents are enclosed with paragrphs tags, the parapgraph tags are removed.

 For example:

    <td style="text-align: left;"><p>die Frau</p></td>

 will become

    <td style="text-align: left;">die Frau<</td>

 Note: The enclosing </p> tag need not be present in the input. 
*/

function remove_p_tag(string $subject)
{
  static $regex = '%^(<td(?>[^>]+)?>)<p>(.*)(?:</p>)?</td>%';

  static $replace = '$1$2</td>';
  
  $result = preg_replace($regex, $replace, $subject);

  return $result;
}

$files = array("curriculum-unit1", "curriculum-unit2", "curriculum-unit3", "curriculum-unit4", "curriculum-unit5");

foreach($files as $file) {

   $in  = new FileReader($file . ".html");

   $out = new File($file . ".new", "w");

   foreach($in as $line) {

     if (str_starts_with($line, "<td")) 

         $line = remove_p_tag($line);

     $out->fwrite($line . "\n"); 
   }
}
