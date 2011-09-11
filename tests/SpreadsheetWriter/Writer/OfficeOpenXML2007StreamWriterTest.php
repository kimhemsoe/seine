<?php
namespace SpreadSheetWriter\Writer;

require_once(dirname(dirname(__DIR__)) . '/bootstrap.php');

use SpreadSheetWriter\Parser\DOM\DOMFactory;

class OfficeOpenXML2007StreamWriterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DOMFactory
     */
    private $factory;
    
    public function setUp()
    {
        parent::setUp();
        $this->factory = new DOMFactory();
    }
    
    public function testMultipleSheetsWithStyles()
    {
        $actual_file = __DIR__ . '/_files/actual_valid.xlsx';
        
        $fp = $this->makeStream($actual_file);
        
        $book = $this->makeBookWithWriter($fp);
        $sheet = $book->addSheetByName('more1');
        for($i = 0; $i < 10; $i++) {
            $sheet->addRow($this->factory->getRow(array(
                'cell1',
                'cell"2',
                'cell</Cell>3',
                'cell4'
            )));
        }

        $sheet = $book->addSheetByName('mor"e2');
        $style = $sheet->addStyleById('mystyle')->setFontBold(true);
        $sheet->addRow($this->factory->getRow(array('head1', 'head2', 'head3', 'head4'))->setStyle($style));
        for($i = 0; $i < 10; $i++) {
            $sheet->addRow($this->factory->getRow(array(
                'cell1',
                'ceæøåll2',
                rand(100, 10000),
                'cell4'
            )));
        }
        $book->close();
        fclose($fp);
    }
    
    public function testLargeNumberOfRowsDoesNotImpedeMemory()
    {
        $memory_limit = 20 * 1000 * 1000; // 20 MB
        $time_limit_seconds = 5.0; // 5 seconds
        $num_rows = 10000;
        $num_columns = 25;
        
        $start_timestamp = microtime(true);
        $actual_file = __DIR__ . '/_files/performance.xlsx';
        
        $fp = $this->makeStream($actual_file);
        
        $book = $this->makeBookWithWriter($fp);
        $sheet = $book->addSheetByName('more1');
        for($i = 0; $i < $num_rows; $i++) {
            $sheet->addRow($this->factory->getRow(range(0, $num_columns)));
        }
        $book->close();
        fclose($fp);
        
        $this->assertLessThan($memory_limit, memory_get_peak_usage(true), 'memory limit reached');
        $this->assertLessThan($time_limit_seconds, (microtime(true) - $start_timestamp), 'time limit reached');
    }
    
    private function makeStream($filename)
    {
        return fopen($filename, 'w');
    }
    
    private function makeBookWithWriter($fp)
    {
        $book = $this->factory->getBook();
        $writer = $this->factory->getWriterFactory()->getOfficeOpenXML2007StreamWriter($fp);
        $writer->setTempDir(__DIR__ . '/_files');
        $book->setWriter($writer);
        return $book;
    }
}