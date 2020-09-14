<?php

namespace Selecto\Product\Ui\Component\Listing\Column;

class Profit extends \Magento\Ui\Component\Listing\Columns\Column
{

    public function __construct(
        \Magento\Framework\View\Element\UiComponent\ContextInterface $context,
        \Magento\Framework\View\Element\UiComponentFactory $uiComponentFactory,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        array $components = [],
        array $data = []
    ) {
        $this->productRepository = $productRepository;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }


    public function prepareDataSource(array $dataSource) {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                $name = $this->getData('name');
                if ($this->getCostPrice($item['entity_id']) !== 0 ) {
                    if($this->getSalePrice($item['entity_id']!= $this->getCostPrice($item['entity_id']))){
                       $item[$name] = (($this->getSalePrice($item['entity_id']))-($this->getCostPrice($item['entity_id']))) * $item['qty']; 
                    }
                    else{
                        $item[$name] = 0;
                    }
                    //$item[$name] =  $specialPrice.$regularPrice.$item['qty'];
                } else {
                    $item[$name] = '-';
                }
            }
        }

        return $dataSource;
    }

    public function getCostPrice($productId) {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $product = $objectManager->create('Magento\Catalog\Model\Product')->load($productId);

        if ($product->getTypeId() == 'simple') {
            $regularPrice = $product->getPriceInfo()->getPrice('regular_price')->getValue();
        } else if ($product->getTypeId() == 'configurable') {
            $basePrice = $product->getPriceInfo()->getPrice('regular_price');
            $regularPrice = $basePrice->getMinRegularAmount()->getValue();
        } else if ($product->getTypeId() == 'bundle') {
            $regularPrice = $product->getPriceInfo()->getPrice('regular_price')->getMinimalPrice()->getValue();
        } else if ($product->getTypeId() == 'grouped') {
            $usedProds = $product->getTypeInstance(true)->getAssociatedProducts($product);
            foreach ($usedProds as $child) {
                if ($child->getId() != $product->getId()) {
                    $regularPrice += $child->getPrice();
                }
            }
        } else {
            $regularPrice = $product->getPriceInfo()->getPrice('regular_price')->getValue();
        }

        return $regularPrice;
    }
    
    public function getSalePrice($productId) {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $product = $objectManager->create('Magento\Catalog\Model\Product')->load($productId);

        if ($product->getTypeId() == 'simple') {
            $specialPrice = $product->getPriceInfo()->getPrice('special_price')->getValue();
        } else if ($product->getTypeId() == 'configurable') {
            $basePrice = $product->getPriceInfo()->getPrice('regular_price');
            $specialPrice = $product->getFinalPrice();
        } else if ($product->getTypeId() == 'bundle') {
            $specialPrice = $product->getPriceInfo()->getPrice('final_price')->getMinimalPrice()->getValue();
        } else if ($product->getTypeId() == 'grouped') {
            $usedProds = $product->getTypeInstance(true)->getAssociatedProducts($product);
            foreach ($usedProds as $child) {
                if ($child->getId() != $product->getId()) {
                    $specialPrice += $child->getFinalPrice();
                }
            }
        } else {
           $specialPrice = $product->getPriceInfo()->getPrice('special_price')->getValue();
        }

        return $specialPrice;
    }

}