<?php
namespace Germania\OrderDispatcher;

use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Psr\Log\LoggerAwareTrait;

use Germania\OrderDispatcher\Exceptions\ItemInvalidUserDataException;
use Germania\OrderDispatcher\Exceptions\ItemNotOrderedException;
use Germania\OrderDispatcher\Exceptions\ItemNotAvailableException;

class ContainerItemFactory extends ItemFactoryAbstract implements ItemFactoryInterface
{

    use LoggerAwareTrait;


    /**
     * @var ContainerInterface
     */
    public $available_articles;


    /**
     * @var string
     */
    public $container_key;


    /**
     * @param ContainerInterface    $available_articles
     * @param string                $container_key
     * @param LoggerInterface|null  $logger
     */
    public function __construct( ContainerInterface $available_articles, string $container_key, LoggerInterface $logger = null )
    {
        $this->setArticlesContainer($available_articles);
        $this->container_key = $container_key;
        $this->setLogger( $logger ?: new NullLogger );
    }



    /**
     * @param ContainerInterface $available_articles [description]
     */
    public function setArticlesContainer(ContainerInterface $available_articles)
    {
        $this->available_articles =  $available_articles;
        return $this;
    }



    /**
     * @inheritDoc
     * @return Item
     * @throws ItemNotAvailableException
     */
    public function createItem( array $ordered_item ) : ItemInterface
    {
        $id = $ordered_item[ $this->container_key ] ?? null;

        try {
            $item = $this->available_articles->get($id);
        }
        catch (NotFoundExceptionInterface $e) {
            $msg = sprintf("Article '%s' not available", $id);
            $this->logger->warning($msg);
            throw new ItemNotAvailableException($msg, 1, $e);
        }


        $item = new Item($item);

        return $item;
    }
}
