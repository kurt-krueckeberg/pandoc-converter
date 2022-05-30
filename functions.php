<?php
declare(strict_types=1);

class FileReader extends \SplFileObject {

  public function __construct(string $fname)
  {
     parent::__construct($fname, "r");

     $this->setFlags(\SplFileObject::READ_AHEAD | \SplFileObject::SKIP_EMPTY | \SplFileObject::DROP_NEW_LINE);
  }
}

/*
 If a table cell tag '<td .....>' is not immediately followed by a <p> tag, then the cell contents are enclosed within beginning and ending parapgraph tags.
 For example:

    <td style="text-align: left;">die Frau</td>

 will become 

    <td style="text-align: left;"><p>die Frau</p></td>

 Comment:
 The regex assumes that the entire cell is in the $subject, it is on one line. The regex will fail if '</td>' is not on the line.
 However, table cells that are NOT followed by a <p> tag seems to fit on one line.
*/
function add_p_tag(string $subject)
{
  static $regex = '%^(<td(?>[^>]+)?>)(?!<p>)(.*)</td>%';

  static $replace = '$1<p>$2</p></td>';
  
  $result = preg_replace($regex, $replace, $subject);

  return $result;
}

function fix_td_tags(string $base_name)
{
   $html_name = $base_name . '.html';
   
   $infile  = new FileReader($html_name);
   
   $out_fname = $base_name . ".new"; 
   
   $outfile = new SplFileObject($out_fname, "w");

   foreach($infile as $line) {

      if (str_starts_with($line, "<td")) $line = add_p_tag($line);

      $outfile->fwrite($line . "\n"); 
   }

   // Move file-name.new to file-name.html
   $cmd = "mv $out_fname " . $html_name ;
   system( $cmd ); 

   echo "$html_name created.\n";
}
