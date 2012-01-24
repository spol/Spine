<?php

namespace Spine\Command;

use Symfony\Component\Console as Console;

class RenderCommand extends Console\Command\Command
{
	public function __construct()
	{
		parent::__construct('build');

		$this->setDescription('Renders markdown to HTML.');
		$this->setHelp('Renders markdown to HTML.');
		$this->addArgument('path', Console\Input\InputArgument::REQUIRED, 'The path of the folder containing the markdown files.');
	}

	protected function execute(Console\Input\InputInterface $input, Console\Output\OutputInterface $output)
	{
		$path = $input->getArgument('path');
		
		if ($path[0] != '/')
		{
			$path = getcwd() . '/' . $path;
		}

		if (!file_exists($path))
		{
			throw new \Exception("path doesn't exist");
		}
		elseif (!is_dir($path))
		{
			throw new \Exception('path is not a directory.');
		}

		$this->buildpath = realpath($path) . '/_epub';
		if (file_exists($this->buildpath))
		{
			$this->unlink_dir($this->buildpath);
		}
		mkdir($this->buildpath);

		file_put_contents($this->buildpath . '/mimetype', "application/epub+zip");
		mkdir($this->buildpath . '/OEBPS/Text', 0777, true);

		$chapters = array();

		if (file_exists($path . '/contents.txt'))
		{
			$files = explode("\n", file_get_contents($path . '/contents.txt'));
			foreach ($files as $file)
			{
				if ($file != "")
				{
					if (file_exists($path.'/'.$file))
					{
						$chapters[] = new Chapter($path.'/'.$file);
					}
					else
					{
						echo "WARNING: File listed in contents.txt not found: {$file}\n";
					}
				}
			}
		}
		else
		{
			$files = new \GlobIterator($path.'/*.txt');
	
			foreach ($files as $file)
			{
				$chapters[] = new Chapter($file->getPathname());
			}
	
			// todo: optionally load order from contents.txt
			usort($chapters, function($a, $b) {
	
				$a = pathinfo($a->getPath(), PATHINFO_FILENAME);
				$b = pathinfo($b->getPath(), PATHINFO_FILENAME);
	
				$a_ = strlen($a) - strlen(ltrim($a, "_"));
				$b_ = strlen($b) - strlen(ltrim($b, "_"));
				
				if ($a_ != $b_) {
					return $a > $b ? -1 : 1;
				}
	
				return strnatcasecmp($a, $b);
			});
		}


		foreach ($chapters as $chap)
		{
			$chap->RenderAndSave($this->buildpath);
		}

		$meta = array(
			'title' => '',
			'author' => '',
			'isbn' => ''
		);
		if (file_exists($path.'/meta.ini'))
		{
			$meta = array_merge($meta, parse_ini_file($path.'/meta.ini'));
		}

		if (!isset($meta['sort_author']))
		{
			$meta['sort_author'] = $meta['author'];
		}

		// images
		$images = new \GlobIterator($path.'/*.jpg');
		
		if (count($images) > 0)
		{
			mkdir($this->buildpath.'/OEBPS/Images');
			foreach ($images as $image)
			{
				copy($image->getPathname(), $this->buildpath.'/OEBPS/Images/'.$image->getFilename());
			}
		}

		mkdir($this->buildpath.'/OEBPS/Styles');
		copy(ROOT . '/tpl/epub.css', $this->buildpath.'/OEBPS/Styles/epub.css');

		// cover
		if (file_exists($this->buildpath.'/OEBPS/Images/cover.jpg'))
		{
			$meta['cover'] = true;
		}
		else
		{
			$meta['cover'] = false;
		}

		// content.opf
		$this->buildContent($chapters, $meta);

		// toc.ncx
		$this->buildTOC($chapters, $meta);
		
		// meta-inf
		$this->buildMetaInf();

		$this->zip($this->file_title($meta['title'], ' '). '.epub');

		if (file_exists($this->buildpath))
		{
			$this->unlink_dir($this->buildpath);
		}
	}

	private $buildpath;

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

	private function buildContent($contents, $meta)
	{
		ob_start();
		extract($meta);

		include ROOT . '/tpl/content.opf.tpl';

		$content = ob_get_clean();

		file_put_contents($this->buildpath.'/OEBPS/content.opf', $content);
	}

	private function buildTOC($contents, $meta)
	{
		ob_start();
		extract($meta);

		include ROOT . '/tpl/toc.ncx.tpl';

		$content = ob_get_clean();

		file_put_contents($this->buildpath.'/OEBPS/toc.ncx', $content);
	}

	private function zip($name)
	{
		echo $name, "\n";
		copy(ROOT.'/tpl/epub.zip', $name);
		$epub = new Zipper();
		$res = $epub->open($name);

		$epub->addDir($this->buildpath.'/META-INF', 'META-INF/');
		$epub->addDir($this->buildpath.'/OEBPS', 'OEBPS/');
//		$epub->addFile($this->buildpath.'/mimetype', 'mimetype');

		$res = $epub->close();
	}
	
	private function buildMetaInf()
	{
		mkdir($this->buildpath.'/META-INF');
		copy(ROOT.'/tpl/container.xml', $this->buildpath.'/META-INF/container.xml');
	}

	function file_title($str)
	{
		$trans = array(
						'&\#\d+?;'				=> '',
						'&\S+?;'				=> '',
						'\s+'					=> ' ',
						'[^a-z0-9\-\._ ]'		=> '',
						'[_\-]+'				=> ' ',
						'[_\- ]$'				=> '',
						'^[\-_ ]'				=> '',
						'\.+$'					=> ''
					);

		$str = strip_tags($str);
		foreach ($trans as $key => $val)
		{
			$str = preg_replace('#'.$key.'#i', $val, $str);
		}

		return trim(stripslashes($str));
	}
}