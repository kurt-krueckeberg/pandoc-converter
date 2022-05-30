<?php
declare(strict_types=1);

include "functions.php";

if ($argc != 3) {
   echo "Enter the start folder, followed the regex the filenames must match.\n";
   return;
}
   
$start_dir = $argv[1];
$regex = '%' . $argv[2] . '%i';

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
