<?php

namespace SymfonyXmlResponse\Responses;

use Symfony\Component\HttpFoundation\Response;

class XmlResponse extends Response
{
    protected $data = '';
    protected $xml_writer;
    public $rootElementName = 'document';
    public $xsltFilePath = '';

    public function __construct($data = null, $status = 200, $headers = array())
    {
        parent::__construct('', $status, $headers);
        $this->xml_writer = new \XMLWriter();

        if ($data != null)
        {
            $this->setDataAndGetDocument($data);
        }

    }

    public static function create($data = null, $status = 200, $headers = array())
    {
        return new static($data, $status, $headers);
    }

    public function setDataAndGetDocument($data = array())
    {
        try
        {
            $this->startDocument($this->rootElementName, $this->xsltFilePath);
            $this->fromArray($data, $this->rootElementName);
            $this->data = $this->getDocument();
        }
        catch (\Exception $exception)
        {
            throw $exception;
        }

        return $this->update();
    }

    protected function update()
    {
        if (!$this->headers->has('Content-Type'))
        {
            $this->headers->set('Content-Type', 'application/xml');
        }

        return $this->setContent($this->data);
    }

    protected function startDocument($rootElementName, $xsltFilePath = '')
    {
        $this->xml_writer->openMemory();
        $this->xml_writer->setIndent(true);
        $this->xml_writer->setIndentString(' ');
        $this->xml_writer->startDocument('1.0', 'UTF-8');

        if ($xsltFilePath)
        {
            $this->xml_writer->writePi('xml-stylesheet', 'type="text/xsl" href="' . $xsltFilePath . '"');
        }

        $this->xml_writer->startElement($rootElementName);
    }

    protected function setElement($elementName, $elementText)
    {
        if (!isset($elementName))
        {
            throw new \InvalidArgumentException('Element name cannot be null. ' . var_export($elementName, true));
        }

        if (preg_match('/[a-zA-Z]/', substr($elementName, 0, 1)) !== 1)
        {
            throw new \InvalidArgumentException(
                'Element name must begin with alpha character. ' . var_export($elementName, true)
            );
        }
        $this->xml_writer->startElement($elementName);
        $this->xml_writer->text($elementText);
        $this->xml_writer->endElement();
    }

    protected function fromArray($data, $nameForElement)
    {
        if (is_array($data)) {
            foreach ($data as $index => $element)
            {
                if (is_array($element))
                {
                    $this->xml_writer->startElement($index);
                    $this->fromArray($element, $index);
                    $this->xml_writer->endElement();
                }

                elseif (substr($index, 0, 1) == '@')
                {
                    $this->xml_writer->writeAttribute(substr($index, 1), $element);
                }

                elseif ($index == $nameForElement)
                {
                    $this->xml_writer->text($element);
                }

                else
                {
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