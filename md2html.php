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

if ($argc < 2) {
   echo "Enter the start folder, optionally followed the regex the markdown file names must match.\n";
   return;
}
   
$start_dir = $argv[1];

$regex = isset($argv[2]) ?  $argv[2] : '.*';

$regex = "%$regex" . ".md%i";

$iter = new RecursiveIteratorIterator(  new RecursiveDirectoryIterator($start_dir) );

$md_filter_iter = new \CallbackFilterIterator($iter, function(\SplFileInfo $info) use ($regex) { 

                                                      return $info->isfile()  && (1 == preg_match($regex, $info->getfilename()) ) ? true : false;
                                                  });

// Create closure to call pandoc to convert .md to .html file using the pandoc html template given below.
$template_name = '/usr/local/bin/pandoc-dark-template';

$md2html = function(\SplFileInfo $info) use ($template_name) 
{
   $base_name = $info->getBasename('.md');

   $output = $base_name . '.html'; 
   
   $cmd =  'pandoc ' . $info->getPathname() . ' --template ' . $template_name . ' -t html --metadata title=$base_name -s -o ' . $output;

   system( $cmd );

   fix_td_tags($base_name);
};

// Invoke pandoc for each file matching the regex.
foreach ($md_filter_iter as $info) $md2html($info);
