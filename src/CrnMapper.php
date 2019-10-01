<?php
namespace CroudTech;

use \Illuminate\Support\Collection;

class CrnMapper
{
    public $systemId;
    public $internalRoutingFormat;
    public $serviceMap;
    public $serviceName;

    public function __construct()
    {
        $this->systemId = getenv('SYSTEM_ID');
        $this->internalRoutingFormat = getenv('INTERNAL_ROUTING_FORMAT');
        $this->serviceName = getenv('SERVICE_NAME');
        $this->serviceMap = json_decode(getenv('SERVICE_MAP'), true);
    }

    /**
     * Gets the system map block for a given $systemId
     *
     * @param [type] $systemId
     * @return array
     */
    protected function getMap(string $systemId)
    {
        if (!isset($this->serviceMap['systems'][$systemId])) {
            throw new \Exception('Invalid System ID');
        }

        return $this->serviceMap['systems'][$systemId];
    }

    /**
     * Get the public domain
     * 
     * Gets the public domain name / serviceName for the given $map block
     *
     * @param [type] $map
     * @param [type] $serviceName
     * @return string
     */
    protected function getPublicDomain($map, $serviceName)
    {
        $suffix = substr($map['domain'], -1) !== '/' ? '/' : '';
        return str_replace('<serviceName>', $serviceName, $map['domain']) . $suffix;
    }

    /**
     * Get the internal domain
     * 
     * Gets the internal domain name / serviceName for the given $map block
     *
     * @param [type] $map
     * @param [type] $serviceName
     * @return string
     */
    protected function getInternalDomain($map, $serviceName)
    {
        if (!isset($map['namespace'])) {
            throw new \Exception('Service namespace not found');
        }

        $suffix = substr($this->internalRoutingFormat, -1) !== '/' ? '/' : '';

        return str_replace('<serviceName>', $this->getAlias($map, $serviceName), 
            str_replace('<serviceNamespace>', $map['namespace'], $this->internalRoutingFormat))
            . $suffix;
    }

    /**
     * Gets the alias for the serviceName
     *
     * @param [type] $map
     * @param [type] $serviceName
     * @return array
     */
    protected function getAlias($map, $serviceName)
    {
        if (!isset($map['aliases']) || !isset($map['aliases'][$serviceName])) {
            throw new \Exception('Service alias not found');
        }
        return $map['aliases'][$serviceName];
    }

    public function urlFromCrn($crn, $type)
    {
        $method = sprintf('get%sDomain', ucfirst($type));
        $pieces = explode(':', $crn);
        return $this->{$method}($this->getMap($pieces[0]), $pieces[1]) 
            . implode('/', array_slice($pieces, 2));
    }

    /**
     * Gets Gets Public URL from Crn
     *
     * @param [string] $crn
     * @return string
     */
    public function publicUrlFromCrn($crn)
    {
        return $this->urlFromCrn($crn, 'Public');
    }

     /**
     * Gets Gets Internal URL from Crn
     *
     * @param [string] $crn
     * @return string
     */
    public function internalUrlFromCrn($crn)
    {
        return $this->urlFromCrn($crn, 'Internal');
    }

    /**
     * Creates a CRN from an array of entity > ids
     *
     * @param [array] $entityParams
     * @return string
     */
    public function createCrn(array $entityParams)
    {
        return collect($entityParams)->reduce(function($str, $row){
            if (!isset($row['entity'] ) || !isset($row['id'])) {
                throw new \Exception('Incorrect paramter definition');
            }
            return $str . ':' . $row['entity'] . ':' . $row['id'];
        }, $this->systemId . ':' . $this->serviceName);
    }
}
