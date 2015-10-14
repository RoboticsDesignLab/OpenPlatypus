<?php
use Symfony\Component\HttpFoundation\File\UploadedFile;

class UserFileTest extends TestCase {
    private $testFileName = "tst_file.txt";
    private $testFileExt = "txt";
    private $mockFileUpload;

    // Proper value set in setUp(). Initial value set here
    // to avoid "variable may be used before defined message"
    private $testFilePath = "";


    public function setUp() {
        parent::setUp();

        $this->testFilePath = __DIR__ . DIRECTORY_SEPARATOR . $this->testFileName;
        file_put_contents($this->testFilePath, "Test text/plain file.");
        $this->mockFileUpload = new UploadedFile($this->testFilePath, $this->testFileName, "text/plain", 232041, 0, false);
    }

    public function tearDown() {
        if (file_exists($this->testFilePath)) {
            unlink($this->testFilePath);
        }
    }

    private function mockUpload($action) {
        $response = $this->action('POST', $action, [], [], [$this->mockFileUpload]);
        $this->assertResponseOk();
        return $response;
    }

    public function testCreateDeleteFile() {
        $UserFileController = new UserFileController;

        $userFile = $UserFileController->createFile($this->testFilePath, $this->testFileExt);
        $userFileId = $userFile->id;
        $userFilePath = $userFile->getPath();
        $this->assertTrue(file_exists($userFilePath));
        $this->assertNotEquals(null, UserFile::find($userFileId));

        $userFile->delete();
        $this->assertFalse(file_exists($userFilePath));
        $this->assertEquals(null, UserFile::find($userFileId));
    }

    // Create modified UserFile in which placeFile() always
    // fails. Use that to test.
    public function testSavePlaceFileFail() {
    }

    public function testDBSaveFail() {

    }
}
