<?php

namespace Ijscoman1337\XmlResponse;

use Symfony\Component\HttpFoundation\Response;

class XmlResponse extends Response
{
    protected $data = '';
    protected $xml_writer;
    public $root_element_name = 'document';
    public $xslt_file_path = '';

    public function __construct($data = null, $status = 200, $headers = array())
    {
        parent::__construct('', $status, $headers);
        if (null === $data) {
            $data = new \ArrayObject();
        }
        $this->xml_writer = new \XMLWriter();
        if (!is_null($data)) {
            $this->setData($data);
        }
    }

    public static function create($data = null, $status = 200, $headers = array())
    {
        return new static($data, $status, $headers);
    }

    public function setData($data = array())
    {
        try {
            $this->startDocument($this->root_element_name, $this->xslt_file_path);
            $this->fromArray($data, $this->root_element_name);
            $this->data = $this->getDocument();
        } catch (\Exception $exception) {
            throw $exception;
        }
        return $this->update();
    }

    protected function update()
    {
        if (!$this->headers->has('Content-Type')) {
            $this->headers->set('Content-Type', 'application/xml');
        }
        return $this->setContent($this->data);
    }

    protected function startDocument($prm_rootElementName, $prm_xsltFilePath = '')
    {
        $this->xml_writer->openMemory();
        $this->xml_writer->setIndent(true);
        $this->xml_writer->setIndentString(' ');
        $this->xml_writer->startDocument('1.0', 'UTF-8');
        if ($prm_xsltFilePath) {
            $this->xml_writer->writePi('xml-stylesheet', 'type="text/xsl" href="' . $prm_xsltFilePath . '"');
        }
        $this->xml_writer->startElement($prm_rootElementName);
    }

    protected function setElement($prm_elementName, $prm_ElementText)
    {
        if (!isset($prm_elementName)) {
            throw new \InvalidArgumentException('Element name cannot be null. ' . var_export($prm_elementName, true));
        }
        if (preg_match('/[a-zA-Z]/', substr($prm_elementName, 0, 1)) !== 1) {
            throw new \InvalidArgumentException(
                'Element name must begin with alpha character. ' . var_export($prm_elementName, true)
            );
        }
        $this->xml_writer->startElement($prm_elementName);
        $this->xml_writer->text($prm_ElementText);
        $this->xml_writer->endElement();
    }

    protected function fromArray($prm_array, $prm_name)
    {
        if (is_array($prm_array)) {
            foreach ($prm_array as $index => $element) {
                if (is_array($element)) {
                    $this->xml_writer->startElement($index);
                    $this->fromArray($element, $index);
                    $this->xml_writer->endElement();
                } elseif (substr($index, 0, 1) == '@') {
                    $this->xml_writer->writeAttribute(substr($index, 1), $element);
                } elseif ($index == $prm_name) {
                    $this->xml_writer->text($element);
                } else {
                    $this->setElement($index, $element);
                }
            }
        }
    }

    protected function getDocument()
    {
        $this->xml_writer->endElement();
        $this->xml_writer->endDocument();
        return $this->xml_writer->outputMemory();
    }
}