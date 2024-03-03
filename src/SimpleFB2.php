<?php

namespace SimpleFB2;

use SimpleFB2\Exception;
use SimpleFB2\Exception\FileExistException;
use SimpleFB2\Exception\NodeException;
use SimpleFB2\Exception\EmptyDataException;
use SimpleFB2\Exception\SetFileException;
use SimpleFB2\Helper;
use \XMLReader;
use \DOMDocument;
use Exception as SystemException;


/**
* Class realizes open file fb2 format
*
* @param array $all_notes 
* @param XMLReader $reader
* @param DOMDocument $doc
*
*/
class SimpleFB2
{
    public $all_notes = [];
    protected $book;

    /** 
     * XmlReader cursor
    */
    protected $cursor;
    protected $doc;
    protected $cover_path;

    protected $description;

    /**
    * Class constructor
    *
    * @param string $book
    */
    public function __construct($book)
    {
        $this->doc = new DOMDocument;
        $this->cursor = new XMLReader;
        $this->book = $book;
        if ($book != null || $book != "") {
            if (file_exists($book)) {
                $this->readDescription();
            } else {
                throw new FileExistException();
            }
        } else {
            throw new SetFileException();
        }
    }

    public function setCoverPath($path)
    {
        $this->cover_path = $path ;
    }

    public function getCoverPath()
    {
        return $this->cover_path;
    }

    /**
    * Returns description of book
    *
    * @return $this
    */
    protected function readDescription() 
    {
        $this->cursor->open($this->book);
        while ($this->cursor->read()) { 
            if ($this->cursor->nodeType == XMLReader::ELEMENT) {
                if ($this->cursor->name == 'description') {
                    $this->description = simplexml_import_dom($this->doc->importNode($this->cursor->expand(), true));
                    return $this;
                } 
            } else {
                throw new NodeException();
            }
        }

        return $this;
    }

    /**
    * Returns genres
    *
    * @return array
    */
    public function getGenres()
    {
        return (array)$this->description->{'title-info'}->genre;
    }

    /**
    * Returns author of book
    *
    * @return string
    */

    public function getAuthor()
    {
        $str = '';
        $author = [];
        $author_fio = (array)$this->description->{'title-info'}->author;
        if (empty($author_fio)) throw new FieldException('author');
        foreach ($author_fio as $key => $val) {
            if (preg_match("/name/", $key)) {
                $str .= sprintf('%s ',$val);
            }
        }
        return $str;
    }

    /**
    * Returns book name
    *
    * @return string
    */

    public function getBookName()
    {
        return (string)$this->description->{'title-info'}->{'book-title'};
    }

    /**
    * Returns publication date
    *
    * @return string
    */

    public function getDate()
    {
        return (string)$this->description->{'title-info'}->{'date'};
    }

    /**
    * Cover of book
    *
    * Create and returns cover image of book
    *
    * @return string
    */
    public function getCover()
    {
        $cover_href = (string)$this->description->{'title-info'}->coverpage[0]->image[0]->attributes('l', true)->href;
        while ($this->cursor->read()) {
            if ($this->cursor->nodeType == XMLReader::ELEMENT) {
                if ($this->cursor->name == 'binary' && $this->cursor->getAttribute('content-type') == 'image/jpeg') {
                    if ($this->cursor->getAttribute('id') == 'cover.jpg') {
                        $this->createCover($this->cursor);
                    }
                }
            }
        }

        return "";
    }

    private function createCover($xml_reader)
    {
        while ($xml_reader->read()) {
            if ($xml_reader->nodeType == XMLReader::TEXT) {
                $img_in_str = base64_decode($xml_reader->value);
                $im = imagecreatefromstring($img_in_str);
                if ($im !== false) {
                    $img_name = sprintf('%s/%s.jpg',$this->getCoverPath(),Helper::generateRandomString());
                    imageJpeg($im, $img_name, 100);
                    print $img_name;
                    return;
                } else {
                    throw new EmptyDataException('Failed to create image');
                }

            }
        }
    }

    /**
     * Get Annotations
     * 
     * @return string
     */
    public function getAnnotation() {
        return $this->description->{'annotation'};
    }

    /**
     * Get publication data
     * 
     * @return array
     */
    public function getPublishInfo() {
        $publishInfo = [];
        $cursorPubInfo = $this->description->{'publish-info'};
        $publishInfo['publisher'] = (string)$cursorPubInfo->{'publisher'};
        $publishInfo['city'] = (string)$cursorPubInfo->{'city'};
        $publishInfo['year'] = (string)$cursorPubInfo->{'year'};
        return $publishInfo;
    }

    /**
     * Get Custom Info
     * 
     * @return string
     */
    public function getCustomInfo() {
        return (string)$this->description->{'custom-info'};
    }

    /**
    * Book text
    *
    * Returns  common text of book
    *
    * return string
    */
    public function getText()
    {   
        $reader = new XMLReader;
        $reader->open($this->book);
        $str = '';
        while ($reader->read()) { 
            if ($reader->nodeType == XMLReader::ELEMENT) {
                if ($reader->name== 'body') { 
                    $xml_object = simplexml_import_dom($this->doc->importNode($reader->expand(), true));
                    $str = $this->readText($xml_object);
                }
            }
        }
        return $str;
    
    }
    
    /**
    * Read text
    *
    * This method read text of book.
    * 
    * @var object
    * @return string
     */
    private function readText($body)
    {
        foreach ($body as $key => $val) {
            switch ($key) {
                case 'title':
                    foreach ($body->$key as $title) {
                        foreach ($title as $name => $name_val) {
                            print (string)$name_val . "<br>";
                        }
                    }
                    break;
                case 'epigraph':
                    $this->readText($val);
                    break;
                case 'section':
                    $this->readText($val);
                    break;
                case 'poem':
                    print "<b>";
                    $this->readText($val);
                    print "</b>";
                    break;
                case 'cite':
                    print "<i>";
                    $this->readText($val);
                    print "</i>";
                    break;
                case 'subtitle':
                    print "<br>";
                    print "<i>";
                    $this->readText($val);
                    print "</i>";
                    break;
                case 'stanza':
                    print "<br>";
                    $this->readText($val);
                    print "<br>";
                    break;
                case 'p':
                    print (string)$val . "<br>";
                    if ($val->a) {
                        $this->getNote($val->a);
                    }
                    break;
                case 'v':
                    print (string)$val . "<br>";
                    break;
            }
        }
    }

    /**
    * Get annotation to book
    *
    * @var XMLReader
    * @return string
    */

    private function getNote($a)
    {   
        if ($a->attributes()->type == 'note') {
            return (string)$a->attributes('l', true)->href;
        }

        return "";
    }

    /**
    * Returns annotation
    *
    * @return mixed
    */
    public function readNotes()
    {
        $notes_list = [];
        $reader = new XMLReader;
        $reader->open($this->book);
        while ($reader->read()) {
            if ($reader->nodeType == XMLReader::ELEMENT) {
                if ($reader->name == 'body' && $reader->getAttribute('name') == 'notes') {
                    $notes_dom = simplexml_import_dom($this->doc->importNode($reader->expand(), true));
                    foreach ($notes_dom->section as $section) {
                        $note_id = (string)$section->title->p;
                        foreach ($section as $val) {
                            $notes_list[$note_id] = (string)$val; 
                        }
                        $this->all_notes[(string)$section->attributes()->id] = trim($section->description);
                    }
                }
            }
        }
        return $notes_list;
    }

}
