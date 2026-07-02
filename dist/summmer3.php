
<link rel="stylesheet" href="../dist/style.css" />
<script type="text/javascript" src="../dist/tinyeditor.js"></script>
<script type="text/javascript">
new TINY.editor.edit('editor',{
	id:'summernote',
	width:800,
	height:200,
	cssclass:'te',
	controlclass:'tecontrol',
	rowclass:'teheader',
	dividerclass:'tedivider',
	controls:['bold','italic','underline','strikethrough','|','subscript','superscript','|',
			  'orderedlist','unorderedlist','|','outdent','indent','|','leftalign',
			  'centeralign','rightalign','blockjustify','|','unformat','|','undo','redo','n',
			  'font','size','style','|','image','hr','link','unlink','|','cut','copy','paste','print'],
	footer:true,
	fonts:['Verdana','Arial','Georgia','Trebuchet MS'],
	xhtml:true,
	cssfile:'style.css',
	bodyid:'editor',
	footerclass:'tefooter',
	toggle:{text:'show source',activetext:'show wysiwyg',cssclass:'toggle'},
	resize:{cssclass:'resize'}
});
</script>