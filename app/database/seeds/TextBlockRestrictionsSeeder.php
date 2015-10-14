<?php
/**
 * Created by PhpStorm.
 * User: lukejeremy
 * Date: 23/01/15
 * Time: 5:49 PM
 */

class TextBlockRestrictionsSeeder extends Seeder {
    private $categories = array(
        'files',
        'misc',
        'text',
        'barny',
        'categorical',
        'bigdata',
        'startup'
    );

    private $fileExtensions = array(
        'png',
        'gif',
        'jpg',
        'jpeg',
        'doc',
        'docx',
        'pdf',
        'ppt',
        'rtf',
        'txt',
        'zip'
    );

    public function run() {
        for ($i = 0; $i < 20; $i++) {
            TextBlockRestriction::create(array(
                'category' => $this->randomCategory(),
                'text_permitted' => $this->randomBoolean(),
                'text_min_length' => $this->randomInteger32(),
                'text_max_length' => $this->randomInteger32(),
                'file_upload_permitted' => $this->randomBoolean(),
                'file_size_max_bytes' => $this->randomInteger32(),
                'file_extension_allowed_1' => $this->randomFileExtension(),
                'file_extension_allowed_2' => $this->randomFileExtension(),
                'file_extension_allowed_3' => $this->randomFileExtension(),
            ));
        }
    }

    private function randomCategory() {
        return $this->categories[array_rand($this->categories)];
    }

    private function randomBoolean() {
        return mt_rand(0, 1);
    }

    private function randomInteger32() {
        return mt_rand(0, pow(2, 31) - 1);
    }

    private function randomFileExtension() {
        return $this->fileExtensions[array_rand($this->fileExtensions)];
    }

}