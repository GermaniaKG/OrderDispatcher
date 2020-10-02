<?php
namespace Germania\OrderDispatcher;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Psr\Log\LoggerAwareTrait;

use Germania\OrderDispatcher\Exceptions\RequiredUserDataMissingException;
use Germania\OrderDispatcher\Exceptions\NoArticlesOrderedException;
use Germania\OrderDispatcher\Exceptions\ItemExceptionInterface;

class ArrayOrderFactory extends OrderFactoryAbstract implements OrderFactoryInterface
{

    use LoggerAwareTrait;


    public $items_key;

    /**
     * @param ItemFactoryInterface $item_factory
     * @param LoggerInterface|null $logger
     */
    public function __construct( ItemFactoryInterface $item_factory, string $items_key, LoggerInterface $logger = null )
    {
        $this->setItemFactory($item_factory);
        $this->items_key = $items_key;
        $this->setLogger( $logger ?: new NullLogger );
    }



    /**
     * @inheritDoc
     */
    public function createOrder( array $input) : OrderInterface
    {
        $user_data = $this->extractCustomerData($input);
        $ordered_items = $this->extractOrderItems($input);

        $order = new Order($user_data, $ordered_items);
        return $order;
    }


    /**
     * @param  array  $input User input
     * @return array
     *
     * @throws RequiredUserDataMissingException
     */
    protected function extractCustomerData( array $input ) : array
    {
        $customer_data = $this->getValidator()->validate($input);

        if (in_array(false, $customer_data)) {
            $msg = "Missing required user data fields";
            $this->logger->debug($msg, $customer_data);
            throw new RequiredUserDataMissingException($msg);
        }

        return $customer_data;
    }



    /**
     * @param  array  $input User input
     * @return array
     *
     * @throws NoArticlesOrderedException
     */
    protected function extractOrderItems( array $input ) : array
    {
        $ordered_items = array();
        $input_ordered_items = $input[ $this->items_key ] ?? array();

        if (!is_array($input_ordered_items)) {
            $msg = sprintf("Expected '%s' to be array", $this->items_key);
            $this->logger->debug($msg, [
                'userOrderedItems' => gettype($input_ordered_items)
            ]);
            throw new NoArticlesOrderedException($msg);
        }

        foreach($input_ordered_items as $ordered_item) :
            try {
                $item = $this->getItemFactory()->createItem($ordered_item);
                array_push($ordered_items, $item);
            }
            catch (ItemExceptionInterface $e) {
                $this->logger->debug("Ignoring error in item creation", $this->throwableToArray($e));
            }
            catch (\Throwable $e) {
                $this->logger->error("Error creating item", $this->throwableToArray($e));
            }

        endforeach;

        if (empty($ordered_items)) {
            $msg = "No articles ordered";
            $this->logger->debug($msg);
            throw new NoArticlesOrderedException($msg);
        }

        return $ordered_items;
    }




    protected function throwableToArray (\Throwable $e) : array
    {
        return array(
            'type'     => get_class($e),
            'message'  => $e->getMessage(),
            'location' => sprintf("%s:%s", $e->getFile(), $e->getLine())
        );
    }

}


