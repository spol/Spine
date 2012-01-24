<?php

namespace Spine\Command;

use Symfony\Component\Console as Console;

class ExtractCommand extends Console\Command\Command
{
	public function __construct()
	{
		parent::__construct('extract');

		$this->setDescription('Extracts an ePub to Markdown.');
		$this->setHelp('Extracts an ePub to Markdown.');
		$this->addArgument('file', Console\Input\InputArgument::REQUIRED, 'The ePub file.');
	}

	protected function execute(Console\Input\InputInterface $input, Console\Output\OutputInterface $output)
	{
		$file = $input->getArgument('file');
		
		if ($file[0] != '/')
		{
			$file = getcwd() . '/' . $file;
		}

		if (!file_exists($file))
		{
			throw new Exception("File doesn't exist");
		}

		$zip = new \ZipArchive();
		$zip->open($file);

		$container = $zip->statName('META-INF/container.xml');

		if ($container === false)
		{
			throw new Exception("This doesn't appear to be a valid EPUB file.");
		}
		
		$containerXml = $zip->getFromName('META-INF/container.xml');

		$contents = $this->readContainerXml($containerXml);

		$internalPath = pathinfo($contents, PATHINFO_DIRNAME);
		if ($internalPath != '') $internalPath .= '/';

		$contentsXml = $zip->getFromName($contents);

		list($meta, $files, $spine) = $this->readContentsXml($contentsXml);

		// create folder
		$path = pathinfo($file, PATHINFO_DIRNAME) . '/' . pathinfo($file, PATHINFO_FILENAME);

		if (file_exists($path)) $this->unlink_dir($path);

		mkdir($path);

		// save meta
		$this->write_ini_file($meta, $path.'/meta.ini');

		// images (cover)
		foreach ($files['image/jpeg'] as $images)
		{
			$file = pathinfo($images['name'], PATHINFO_BASENAME);
			$imageData = $zip->getFromName($internalPath . $images['name']);
			file_put_contents($path.'/'.$file, $imageData);
		}

		// export files
		$contents = array();
		foreach ($spine as $pos => $id)
		{
			$file = $files['application/xhtml+xml'][$id]['name'];
			$contents[] = ($pos+1) . '_' . pathinfo($file, PATHINFO_FILENAME) . '.txt';
			$target = ($pos+1) . '_' . pathinfo($file, PATHINFO_FILENAME) . '.txt';

			// Markdownify
			$html = $zip->getFromName($internalPath . $file);
			$markdownify = new \Markdownify(false, false, false);
			$markdown = $markdownify->parseString($html);
			file_put_contents($path.'/'.$target, $markdown);
		}

		// contents
		file_put_contents($path.'/contents.txt', implode("\n", $contents));

	}
	
	private function readContainerXml($xml)
	{
		$container = new \SimpleXMLElement($xml);
		return (string)$container->rootfiles->rootfile['full-path'];
	}

	private function readContentsXml($xml)
	{
		$package = new \SimpleXmlElement($xml);

		$meta = array();

		foreach (array_keys($package->getNamespaces(true)) as $namespace)
		{
			//var_dump($namespace);
			foreach ($package->metadata->children($namespace, true) as $element)
			{
				switch ($namespace)
				{
					case "":
						$meta[(string)$element['name']] = (string)$element['content'];
						break;
					case "dc":
						if (isset($meta[$element->getName()]))
						{
							if (!is_array($meta[$element->getName()]))
							{
								$meta[$element->getName()] = array($meta[$element->getName()]);
							}
							$meta[$element->getName()][] = (string)$element;
						}
						else
						{
							$meta[$element->getName()] = (string)$element;
						}
						break;
					case "opf":
						$attr = $element->attributes();
						$meta[(string)$attr['name']] = (string)$attr['content'];
						break;
				}
			}
		}

		$files = array();
		foreach ($package->manifest->item as $item)
		{
			$files[(string)$item['media-type']][(string)$item['id']] = array("name" => (string)$item['href'], "id" => (string)$item['id']);
		}

		$spine = array();
		foreach ($package->spine->itemref as $itemref)
		{
			$spine[] = (string)$itemref['idref'];
		}

		return array($meta, $files, $spine);
	}

	private function write_ini_file($assoc_arr, $path, $has_sections=FALSE) { 
	$content = ""; 
	if ($has_sections) { 
		foreach ($assoc_arr as $key=>$elem) { 
			$content .= "[".$key."]\n"; 
			foreach ($elem as $key2=>$elem2) { 
				if(is_array($elem2)) 
				{ 
					for($i=0;$i<count($elem2);$i++) 
					{ 
						$content .= $key2."[] = \"".$elem2[$i]."\"\n"; 
					} 
				} 
				else if($elem2=="") $content .= $key2." = \n"; 
				else $content .= $key2." = \"".$elem2."\"\n"; 
			} 
		} 
	} 
	else { 
		foreach ($assoc_arr as $key=>$elem) { 
			if(is_array($elem)) 
			{ 
				for($i=0;$i<count($elem);$i++) 
				{ 
					$content .= $key."[] = \"".$elem[$i]."\"\n"; 
				} 
			} 
			else if($elem=="") $content .= $key." = \n"; 
			else $content .= $key." = \"".$elem."\"\n"; 
		} 
	} 

	if (!$handle = fopen($path, 'w')) { 
		return false; 
	} 
	if (!fwrite($handle, $content)) { 
		return false; 
	} 
	fclose($handle); 
	return true; 
}

	private function unlink_dir($dir) {
		$iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir), \RecursiveIteratorIterator::CHILD_FIRST);
		foreach ($iterator as $path) {
			if ($path->isDir()) {
				rmdir($path->__toString());
			} else {
				unlink($path->__toString());
			}
		}
		rmdir($dir);
	}
}