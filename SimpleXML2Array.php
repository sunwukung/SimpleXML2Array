<?php
/**
 *
 * converts XML files into PHP arrays by way of SimpleXml
 * 
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 */
class SimpleXML2Array{

    protected $_results;
    protected $_errors;

    /**
     * convert an xml string into an array
     *
     * @param string $xml
     * @return array $_results
     */
    public function transformXml($xml, $xpath) {
        $xmlObj = simplexml_load_string($xml);
        $this->_errors = $xmlObj->errors;
        $this->_results = array();

        $startNode = $xmlObj->xpath($xpath);
        if (!empty($startNode[0])) {
            $this->_results = $this->_parseTree($startNode[0]);
        }

        return $this->_results;
    }

    /**
     * parsr the xml string into an array
     *
     * @param <type> $node
     * @return <type>
     */
    protected function _parseTree($node) {
        $tree = array();

        // parse the attributes
        $attr = $node->attributes();
        if ($attr) {
            foreach ($attr as $key => $value) {
                $tree[$key] = (string) $value;
            }
        }

        $children = $node->children();
        //remove redundant intermediary nodes
        if ($this->_linkNode($node, $children, $attr)) {
            foreach ($children as $child) {
                $tree = $this->_skipNode($child, $tree);
            }
        } else {
            foreach ($children as $child) {
                $g_children = $child->children();
                //remove redundant intermediary node
                if (!$child->attributes() && $g_children->count() > 0) {
                    $tree = $this->_aggregateNode($child, $tree);
                } else {
                    $tree = $this->_parseNode($child, $tree);
                }
            }
        }

        return $tree;
    }

    /**
     * evaluates a node to determine if it is a "linking node"
     *
     * this is a by product of the transformation from xml to php arrays
     * the former supports multiple nodes with identical names
     * whereas this will cause an array index to over-write in PHP
     * thus this function attempts to determine if this node is of the type produced
     * to compensate for this problem
     *
     * @param SimpleXMLElement $node
     * @param SimpleXMLElement $children
     * @param object $attr
     * @return boolean
     */
    protected function _linkNode($node, $children, $attr) {
        if (!$attr && $children->count() > 0) {
            return true;
        }
        return false;
    }

    /**
     * prevents redundant xml node from being added into the array
     * i.e. node[0] = array()
     *
     * @param SimpleXMLElement $node
     * @param array $tree
     * @return array $tree
     */
    protected function _skipNode($node, $tree) {
        $tree[] = $this->_parseTree($node);
        return $tree;
    }

    /**
     * aggregates child nodes under a single index
     * i.e. node['name'] = array()
     * 
     * @param SimpleXMLElement $node
     * @param array $tree
     * @return array $tree
     */
    protected function _aggregateNode($node, $tree) {
        $tree[$node->getName()] = $this->_parseTree($node);
        return $tree;
    }

    /**
     * standard node parsing method
     *
     * aggregates child structures into independent trees identified by a common index
     * i.e. node['name'][0] = array()
     *
     * @param SimpleXMLElement $node
     * @param array $tree
     * @return array $tree
     */
    protected function _parseNode($node, $tree) {
        $tree[$node->getName()][] = $this->_parseTree($node);
        return $tree;
    }

}

?>
