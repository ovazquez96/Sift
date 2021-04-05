<?php
namespace Transom\SiftModule\Interfaces;

interface EventsInterface {
    public function addBasicProperties($entity);
    public function  addCustomProperties($entity);
    public function  addOptions();
    public function  processDecision($entity);
}