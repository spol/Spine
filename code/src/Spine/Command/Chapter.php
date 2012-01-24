<?php

namespace Spine\Command;

class Chapter {

	private $path;
	private $name;
	private $file;
	private $id;

	public function __construct($path)
	{
		$this->name = trim(str_replace('Ω', '', str_replace('_', ' ', pathinfo($path, PATHINFO_FILENAME))));
		$this->file = str_replace(' ', '_', $this->name) . '.xhtml';
		$this->id = str_replace(' ', '_', $this->name);
		$this->path = $path;
	}

	public function getName()
	{
		return $this->name;
	}

	public function getPath()
	{
		return $this->path;
	}

	public function getId()
	{
		return $this->id;
	}

	public function getFilename()
	{
		return $this->file;
	}

	public function RenderAndSave($buildpath)
	{
		$parser = new \Markdown_Parser();

		$output_file = $buildpath .'/OEBPS/Text/' . $this->file;

		$source = file_get_contents($this->path);

		$source = preg_replace('/^[^#>\n].*\n/m', "$0\n", $source);
		$source = preg_replace('/(^>.*)\n([^>])/m', "$1\n\n$2", $source);
		$source = preg_replace('/\n{2,}/m', "\n\n", $source);

		$rendered_content = $parser->transform($source);

		$rendered_content = preg_replace('/^<p>([^a-z]?[A-Z][A-Z])/m', "<p class='newsection'>$1", $rendered_content);

		$title = "";

		ob_start();

		include ROOT . '/tpl/xhtml.tpl';

		$output = ob_get_clean();

		file_put_contents($output_file, $output);
	}
}