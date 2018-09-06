<?php
namespace Undercloud\Misc;
/**
 * Memory module
 *
 * @package  Surface
 * @author   undercloud <lodashes@gmail.com>
 * @license  https://opensource.org/licenses/MIT MIT
 * @link     http://github.com/undercloud/domquery
 */

use DOMXpath;
use DOMElement;
use DomDocument;
use Exception;

class DomQuery
{
    private $domNodeList;

    private function __construct($domNodeList)
    {
        if (null === $domNodeList) {
            return;
        }

        $this->domNodeList = $domNodeList;
    }

    private function nullOr($key, Closure $closure = null)
    {
        $value = (
            is_numeric($key)
                ? $this->domNodeList[$key]
                : (
                    isset($this->domNodeList[0]->{$key})
                        ? $this->domNodeList[0]->{$key}
                        : null
                )
        );

        $value = $this->searchNextDomElement($key, $value);

        if (null === $value) {
            return;
        }

        if (null !== $closure) {
            $value = $closure($value);
        }

        if ($value instanceof DOMElement) {
            $value = new self([$value]);
        }

        return $value;
    }

    private function searchNextDomElement($key, $value)
    {
        if (null === $value) {
            return;
        }

        if ($value instanceof DOMElement) {
            return $value;
        }

        if (in_array($key, ['firstChild', 'lastChild', 'nextSibling', 'previousSibling'], true)) {
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

    public static function load($html)
    {
        $document = new DomDocument;
        if (false === @$document->loadHTML($html, LIBXML_NOBLANKS)) {
            throw new Exception('Cannot parse HTML');
        }
    
        return new self([$document->documentElement]);
    }

    public function find($selector)
    {
        return $this->nullOr(0, function ($node) use ($selector) {
            return new self(
                (new DOMXpath(
                    $node instanceof DomDocument
                    ? $node
                    : $node->ownerDocument
                ))->query($selector, $node)
            );
        });
    }

    public function findByClass($class)
    {
    	return $this->find(".//*[contains(@class,'{$class}')]");
    }

    public function findById($id)
    {
    	return $this->find("//*[@id='{$id}']");
    }

    public function findByTag($tag)
    {
    	return $this->find(".//{$tag}");
    }

    public function tagName()
    {
        return $this->nullOr(0, function ($node) {
            return $node->tagName;
        });
    }

    public function hasAttr($name)
    {
        return $this->nullOr(0, function ($node) use ($name) {
            return $node->hasAttribute($name);
        });
    }

    public function attr($name)
    {
        return $this->nullOr(0, function ($node) use ($name) {
            return $node->getAttribute($name);
        });
    }

    public function html()
    {
        return $this->nullOr(0, function ($node) {
            return $node->ownerDocument->saveHTML($node);
        });
    }

    public function text()
    {
        return $this->nullOr(0, function ($node) {
            return $node->textContent;
        });
    }

    public function eq($index)
    {
        return $this->nullOr($index);
    }

    public function map($callback)
    {
        $map = [];
        foreach ($this->domNodeList as $node) {
            $map[] = $callback(new self([$node]));
        }

        return $map;
    }

    public function first()
    {
        return $this->nullOr('firstChild');
    }

    public function last()
    {
        return $this->nullOr('lastChild');
    }

    public function next()
    {
        return $this->nullOr('nextSibling');
    }

    public function prev()
    {
        return $this->nullOr('previousSibling');
    }

    public function parent()
    {
        return $this->nullOr('parentNode');
    }

    public function length()
    {
    	if ($this->domNodeList instanceof \DOMNodeList) {
    		return $this->domNodeList->count();
    	}

    	return count($this->domNodeList);
    }
}
