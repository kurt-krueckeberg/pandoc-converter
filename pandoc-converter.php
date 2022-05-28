<?php
declare(strict_types=1);

use \SplFileObject as File;

class FileReader extends \SplFileObject {

  public function __construct(string $fname)
  {
     parent::__construct($fname, "r");

     $this->setFlags(\SplFileObject::READ_AHEAD | \SplFileObject::SKIP_EMPTY | \SplFileObject::DROP_NEW_LINE);
  }
}

$start_dir = '/home/kurt/Documents/Germ201';

$iter = new RecursiveIteratorIterator(  new RecursiveDirectoryIterator($start_dir) );

$md_filter_iter = new \CallbackFilterIterator($iter, function(\SplFileInfo $info) { // File must be file of form 'curriculum.+\.md'

                                                      return ($info->isfile()  && (1 == preg_match("@curriculum.+\.md@i", $info->getfilename())) ) ? true : false;
                                                  });
/*
  Create closure to call pandoc to convert .md to .html file using the template named below.
 */
$template_name = "/usr/local/bin/pandoc-dark-template";

$md2html = function(\SplFileInfo $info) use ($template_name) // Assign closure.
{
   $base_name = $info->getBasename(".md");

   $output = $base_name . ".html"; 
   
   $cmd =  'pandoc ' . $info->getPathname() . " --template " . $template_name . " -t html --metadata title=$base_name -s -o " . $output;

   system( $cmd );  

   echo "$output created\n";
};

// Invoke pandoc one each curriculem markdown file
foreach ($md_filter_iter as $info) $md2html($info);

// Returns only the newly-create .html files....
$html_filter_iter = new \CallbackFilterIterator($iter, function(\SplFileInfo $info) {

                                                      return $info->isfile()  && 'html' == $info->getExtension() ? true : false;
                                                  });
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

/*
 * Fix the html cell so their content is enclosed within paragraphs tags.
 */
foreach($html_filter_iter as $file_info) {

   $html  = new FileReader($file_info->getBasename());
   
   $out_fname = $file_info->getBasename(".html") . ".new"; 
   
   $output = new File($out_fname, "w");

   foreach($html as $line) {

      if (str_starts_with($line, "<td")) $line = add_p_tag($line);

      $output->fwrite($line . "\n"); 
   }
   // Move file-name.new to file-name.html
   $cmd = "mv $out_fname " . $file_info->getBasename();
   system( $cmd ); 

   echo "Corrected table cells in " .  $file_info->getBasename() . "\n";
}
