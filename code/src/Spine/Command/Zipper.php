<?php 

namespace Spine\Command;

class Zipper extends \ZipArchive { 

	public function addDir($path, $prefix)
	{
	    $this->addEmptyDir($prefix);
	    $nodes = glob($path . '/*');
	    foreach ($nodes as $node)
	    {
	        if (is_dir($node))
	        {
	            $this->addDir($node, $prefix.pathinfo($node, PATHINFO_BASENAME).'/');
	        } else if (is_file($node))
	        {
	            $this->addFile($node, $prefix.pathinfo($node, PATHINFO_BASENAME));
	        }
	    }
	}
}