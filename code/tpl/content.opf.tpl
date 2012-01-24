<?xml version="1.0" encoding="utf-8" standalone="yes"?>
<package xmlns="http://www.idpf.org/2007/opf" unique-identifier="BookId" version="2.0">
	<metadata xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:opf="http://www.idpf.org/2007/opf">
		<dc:title><?php echo $title; ?></dc:title>
		<dc:language>en</dc:language>
		<dc:identifier id="BookId" opf:scheme="ISBN"><?php echo $isbn; ?></dc:identifier>
		<dc:creator opf:file-as="<?php $sort_author; ?>" opf:role="aut"><?php echo $author; ?></dc:creator>
		<?php
			if ($cover) {
		?>
			<meta content="cover" name="cover" />
		<?php } ?>
	</metadata>

	<manifest>
		<item id="ncx" href="toc.ncx" media-type="application/x-dtbncx+xml"/>
		<?php
			if ($cover) {
		?>
			<item id="cover" href="Images/cover.jpg" media-type="image/jpeg"/>
		<?php } ?>
		<?php
			foreach ($contents as $content) {
		?>
			<item id="<?php echo $content->getId(); ?>" href="Text/<?php echo $content->getFilename(); ?>" media-type="application/xhtml+xml"/>
		<?php } ?>
		<item id="stylesheet" href="Styles/epub.css" media-type="text/css"/>
	</manifest>

	<spine toc="ncx">
		<?php
			foreach ($contents as $content) {
		?>
			<itemref idref="<?php echo $content->getId(); ?>" />
		<?php } ?>
	</spine>
</package>