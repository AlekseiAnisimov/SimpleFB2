<?php

namespace SimpleFB2;

use SimpleFB2\Exception;
use SimpleFB2\Exception\FieldException;
use SimpleFB2\Exception\NodeException;
use SimpleFB2\Exception\EmptyDataException;
use SimpleFB2\Helper;
use \XMLReader;
use \DOMDocument;


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
    protected $reader;
    protected $doc;
    protected $cover_path;

    protected $xml_object;

    /**
    * Class constructor
    *
    * @param string $book
    */
    public function __construct($book)
    {
        $this->doc = new DOMDocument;
        $this->book = $book;
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
    public function description() 
    {
        $this->reader = NULL;
        $this->reader = new XMLReader;
        $this->reader->open($this->book);
        while ($this->reader->read()) { 
            if ($this->reader->nodeType == XMLReader::ELEMENT) {
                if ($this->reader->name == 'description') {
                    $this->xml_object = simplexml_import_dom($this->doc->importNode($this->reader->expand(), true));
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
        return (array)$this->xml_object->{'title-info'}->genre;
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
        $author_fio = (array)$this->xml_object->{'title-info'}->author;
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
        return (string)$this->xml_object->{'title-info'}->{'book-title'};
    }

    /**
    * Returns publication date
    *
    * @return string
    */

    public function getDate()
    {
        return (string)$this->xml_object->{'title-info'}->{'date'};
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
        $cover_href = (string)$this->xml_object->{'title-info'}->coverpage[0]->image[0]->attributes('l', true)->href;
        while ($this->reader->read()) {
            if ($this->reader->nodeType == XMLReader::ELEMENT) {
                if ($this->reader->name == 'binary' && $this->reader->getAttribute('content-type') == 'image/jpeg') {
                    if ($this->reader->getAttribute('id') == 'cover.jpg') {
                        $this->createCover($this->reader);
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
