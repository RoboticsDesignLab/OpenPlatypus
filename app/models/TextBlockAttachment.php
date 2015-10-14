<?php

class TextBlockAttachment extends PlatypusBaseModel {
    protected $table = 'text_block_attachments';

    // point out that this model uses a presenter to tweak fields whe n showing them in views.
    public static $presenterClass='TextBlockAttachmentPresenter';
    
    public static $relationsData = array (
    		'textBlock'  => array(self::BELONGS_TO, 'TextBlock', 'foreignKey' => 'text_block_id'),
    		'diskFile'  => array(self::BELONGS_TO, 'DiskFile', 'foreignKey' => 'file_id'),
    );    
    
    public static $rules = array(
        'file_name'         => 'required|max:100|harmless_filename',
        'description'       => 'max:500',
    );
    
    public function getMimeType() { 
    	return $this->disk_file->type;
    }

    public function getSize() {
    	return $this->disk_file->size;
    }
    
    
    public function getDowloadResponse($download = false) {
    	return $this->diskFile->getDowloadResponse($this->file_name, $download, $this->updated_at);
    }    
    
    public function fullDiskFileName() {
    	return $this->diskFile->fullDiskFileName();
    }
    
    public function getArchiveFileName($prefix='') {
    	$position = 1;
    	foreach($this->text_block->attachments_ordered as $attachment) {
    		if($attachment->id == $this->id) break;
    		$position++;
    	}
    	
    	if($position < 10) {
    		$position = "0$position";
    	}
    	
    	return $prefix.$position.'_'.$this->file_name;
    	
    	
    }
    
    static public function createTable() {
		Schema::create('text_block_attachments', function ($table) {
			$table->engine = 'InnoDB';
			$table->timestamps();
			
			$table->increments('id');
			
			$table->integer('text_block_id')->unsigned()->indexed();
			$table->foreign('text_block_id')->references('id')->on('text_blocks')->onDelete('cascade');
			
			$table->integer('position')->unsigned();
			$table->index(array ('text_block_id','position'));
			
			$table->integer('file_id')->unsigned()->indexed();
			$table->foreign('file_id')->references('id')->on('disk_files')->onDelete('cascade');
			
			$table->string('file_name', 1000);
			$table->longText('description');
		});
	}
    
}


class TextBlockAttachmentPresenter extends PlatypusBasePresenter {

	public function size() {
		return formatFileSize($this->resource->disk_file->size);
	}


}
