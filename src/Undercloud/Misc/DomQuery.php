<?php
namespace Undercloud\Misc;

/**
 * Easy DOM Query
 *
 * @package  DomQuery
 * @author   undercloud <lodashes@gmail.com>
 * @license  https://opensource.org/licenses/MIT MIT
 * @link     http://github.com/undercloud/domquery
 */

use Closure;
use DOMNode;
use DOMXpath;
use DOMElement;
use DOMNodeList;
use DomDocument;

class DomQuery
{
    /**
     * @var mixed
     */
    private $domNodeList;

    /**
     * Constructor
     *
     * @param mixed $domNodeList entity
     */
    public function __construct($domNodeList)
    {
        if (null === $domNodeList) {
            return;
        }

        $this->domNodeList = $domNodeList;
    }

    /**
     * Resolve element by index
     *
     * @param int $index of element
     *
     * @return DOMNode
     */

    private function resolveDomNodeListIndex($index)
    {
    	if($this->domNodeList instanceof DomNodeList){
    		return $this->domNodeList->item($index);
    	}

    	return $this->domNodeList[$index];
    }

    /**
     * Null value resolver
     *
     * @param  mixed        $key     name
     * @param  Closure|null $closure instance
     *
     * @return mixed
     */
    private function nullOr($key, Closure $closure = null)
    {
        $value = (
            is_numeric($key)
                ? $this->resolveDomNodeListIndex($key)
                : (
                    isset($this->resolveDomNodeListIndex(0)->{$key})
                        ? $this->resolveDomNodeListIndex(0)->{$key}
                        : null
                )
        );

        $value = $this->searchNextDomElement($key, $value);

        if (null === $value) {
            $value = new DomDocument;
        }

        if (null !== $closure) {
            $value = $closure($value);
        }

        if ($value instanceof DOMElement) {
            $value = new self(array($value));
        }

        return $value;
    }

    /**
     * Get next DOM element
     *
     * @param  mixed $key   name
     * @param  mixed $value item
     *
     * @return self
     */
    private function searchNextDomElement($key, $value)
    {
        if (null === $value) {
            return;
        }

        if ($value instanceof DOMElement) {
            return $value;
        }

        if (in_array($key, array('firstChild', 'lastChild', 'nextSibling', 'previousSibling'), true)) {
            if (!($value instanceof DOMElement)) {
                if ('firstChild' === $key) {
                    $key = 'nextSibling';
                } elseif ('lastChild' === $key) {
                    $key = 'previousSibling';
                }
            }

            return $this->searchNextDomElement($key, $value->{$key});
        }

        return $value;
    }

    /**
     * Load HTML from string
     *
     * @param string $html value
     *
     * @return self
     */
    public static function load(DomDocument $document)
    {
        return new self(array($document->documentElement));
    }

    /**
     * Find DOM element
     *
     * @param string $selector target
     *
     * @return self
     */
    public function find($selector)
    {
        return $this->nullOr(0, function ($node) use ($selector) {
            $xpath = new DOMXpath(
                $node instanceof DomDocument
                ? $node
                : $node->ownerDocument
            );

            $domNodeList = $xpath->query($selector, $node);

            return new DomQuery($domNodeList);
        });
    }

    /**
     * Find DOM elements by class name
     *
     * @param string $class selector
     *
     * @return self
     */
    public function findByClass($class)
    {
    	return $this->find(".//*[contains(@class,'{$class}')]");
    }

    /**
     * Find DOM elements by id
     *
     * @param string $id selector
     *
     * @return self
     */
    public function findById($id)
    {
    	return $this->find("//*[@id='{$id}']");
    }

    /**
     * Find DOM elements by tag name
     *
     * @param string $tag selector
     *
     * @return self
     */
    public function findByTag($tag)
    {
    	return $this->find(".//{$tag}");
    }

    /**
     * Get element tag name
     *
     * @return string
     */
    public function tagName()
    {
        return $this->nullOr(0, function (DOMNode $node) {
        	if (property_exists($node, 'tagName')) {
            	return $node->tagName;
        	}
        });
    }

    /**
     * Check attribute exists
     *
     * @param string $name of attribute
     *
     * @return boolean
     */
    public function hasAttr($name)
    {
        return $this->nullOr(0, function (DOMNode $node) use ($name) {
        	if (method_exists($node, 'getAttribute')) {
            	return $node->hasAttribute($name);
            }

            return false;
        });
    }

    /**
     * Get attribute value
     *
     * @param string $name of attribute
     *
     * @return string
     */
    public function attr($name)
    {
        return $this->nullOr(0, function (DOMNode $node) use ($name) {
            if (method_exists($node, 'getAttribute')) {
            	return $node->getAttribute($name);
            }
        });
    }

    /**
     * Convert DOM to html
     *
     * @return string
     */
    public function html()
    {
        return $this->nullOr(0, function (DOMNode $node) {
        	if ($node instanceof DOMElement) {
            	return $node->ownerDocument->saveHTML($node);
            }

            if ($node instanceof DomDocument) {
            	return $node->saveHTML();
            }
        });
    }

    /**
     * Convert DOM to text
     *
     * @return string
     */
    public function text()
    {
        return $this->nullOr(0, function (DOMNode $node) {
            return $node->textContent;
        });
    }

    /**
     * Get DOM element by index
     *
     * @param int $index value
     *
     * @return self
     */
    public function eq($index)
    {
        return $this->nullOr($index);
    }

    /**
     * DOM collection walker
     *
     * @param callable $callback function
     *
     * @return array
     */
    public function map($callback)
    {
        $map = array();
        if($this->domNodeList){
	        foreach ($this->domNodeList as $node) {
	            $map[] = $callback(new self(array($node)));
	        }
	    }

		return $map;
    }

    /**
     * Get first DOM element
     *
     * @return self
     */
    public function first()
    {
        return $this->nullOr('firstChild');
    }

    /**
     * Get last DOM element
     *
     * @return self
     */
    public function last()
    {
        return $this->nullOr('lastChild');
    }

    /**
     *  Get next DOM element
     *
     * @return self
     */
    public function next()
    {
        return $this->nullOr('nextSibling');
    }

    /**
     * Get prev DOM element
     *
     * @return self
     */
    public function prev()
    {
        return $this->nullOr('previousSibling');
    }

    /**
     * Get parent node
     *
     * @return self
     */
    public function parent()
    {
        return $this->nullOr('parentNode');
    }

    /**
     * Get node list count
     *
     * @return int
     */
    public function length()
    {
    	if (null === $this->domNodeList) {
    		return 0;
    	}

    	if ($this->domNodeList instanceof DOMNodeList) {
    		if (method_exists($this->domNodeList, 'count')) {
    			return $this->domNodeList->count();
    		}

    		return $this->domNodeList->length;
    	}

    	return count($this->domNodeList);
    }
}