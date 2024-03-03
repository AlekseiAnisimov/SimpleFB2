### SimpleFB2
#Class for parsing fb2 files

use SimpleFB2\SimpleFB2

$path_to_file =  "/home/username/file.fb2";
$book = new SimpleFB2($path_to_file);

### Returns object description have authors name, book name, piblication date and etc.
### Getting authors of book 
$author = $book->getAuthor();

### Getting genres
$genres = $book->getGenres();

### Getting path to cover
$book->setCoverPath('/home/username/img');
$cover =  $book->getCover();

### Getting text of book
$book->getText();

### Getting annotation list
$book->readNotes();

