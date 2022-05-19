<img src="https://static.germania-kg.com/logos/ga-logo-2016-web.svgz" width="250px">

------



# Germania KG · OrderDispatcher

**Sourcing that stuff out…**



## Installation

```bash
$ composer require germania-kg/order-dispatcher
```



## Classes and interfaces



### Working with orders



#### OrderInterface

    public function getCustomerData() : array;
    public function getItems() : iterable;



#### OrderFactoryInterface

```php
public function createOrder( array $input) : OrderInterface;

public function setItemFactory(ItemFactoryInterface $item_factory);
public function getItemFactory() : ItemFactoryInterface;
```



#### OrderFactoryAbstract

This abstract class implements the Item factory interceptors defined in **OrderFactoryInterface** and uses the **ValidatorTrait**.

```php
public function setItemFactory(ItemFactoryInterface $item_factory);
public function getItemFactory() : ItemFactoryInterface;
```



#### ArrayOrderFactory

Creates an **Order** instance from an array, typically from user input. 

The *ArrayOrderFactory* extends **OrderFactoryAbstract** and implements the **OrderFactoryInterface**. 

The constructor accepts an **ItemFactoryInterface**, the order items array key, and optionally a PSR-3 **LoggerInterface**.

```php
<?php
use Germania\OrderDispatcher\ArrayOrderFactory;
use Germania\OrderDispatcher\FilterValidator;

$item_factory = ... ;
$logger = ...;

$factory = new ArrayOrderFactory($item_factory, "items");
$factory = new ArrayOrderFactory($item_factory, "items", $logger);

$customer_validation = new FilterValidator(array(
  "email"  =>  FILTER_VALIDATE_EMAIL,
  "company" =>  FILTER_SANITIZE_FULL_SPECIAL_CHARS,
  "retailer_number" =>  [
    	"filter" => FILTER_VALIDATE_REGEXP, 
    	"options" => ['regexp'=>"/^[\d\-]+$/"]
   ],
  "privacyAck"      =>  FILTER_VALIDATE_BOOLEAN
));

// Set customer data validation
$factory->setValidator( $customer_validation );
```

Usage with above setup:

```php
$order = $factory->createOrder([
  'email' => "test@test.com",
  'company' => "ACME Corp.",
  'retailer_number' => "12345",
  'privacyAck' => 1,
  
  'items' => array(
    [ 'sku' => "foo" , "quantity" => 5 ]
  )  
]);

$customer = $order->getCustomerData();
$items = $order->getItems();
```



#### OrderHandlerController

This controller class accepts user input from the *ServerRequest* body, creates an Order object underway and delegates it to the given handler.

Its constructor accepts an **OrderFactoryInterface** instance, an **OrderHandlerInterface** instance, and optionally a PSR-3 **LoggerInterface**.

```php
<?php
use Germania\OrderDispatcher\OrderHandlerController;

$factory = ... ;
$handler = ... ;
$logger = ... ;

$controller = new OrderHandlerController($factory, $handler);
$controller = new OrderHandlerController($factory, $handler, $logger);
```

Given a *ServerRequest* and a *Response* object, invoke the controller like this:

```php
$request = $request->withParsedBody(array(
  'email'           => "test@test.com",
  'company'         => "ACME Corp.",
  'retailer_number' => "12345",
  'privacyAck'      => 1,
  'articles' => array(
    [ 'sku' => 'A1', 'quantity' => 100],
    [ 'sku' => 'B2', 'quantity' => 5]
  )
));

$response = $controller($request, $response);

if ($reponse->getStatusCode != 200) {
	echo $resonse->getHeaderLine('X-Order-Dispatch-Message');
}
```

##### Response status codes

| Status code |                                               |
| ----------- | --------------------------------------------- |
| 400         | When *OrderFactoryExceptionInterface* occured |
| 500         | When *OrderHandlerExceptionInterface* occured |
| 500         | any other *Throwable*                         |

##### Response headers

In case of an error, the response object will have a `X-Order-Dispatch-Message` header with the class name of the thrown Exception:

```php
if (200 != $reponse->getStatusCode()) {
	echo $resonse->getHeaderLine('X-Order-Dispatch-Message');
  // Germania\OrderDispatcher\Exceptions\OrderHandlerRuntimeException
}
```





---



### Working with order items

**Order items are the things one can order.**



#### Item class and ItemInterface

The **ItemInterface** extends `\ArrayAccess`.

The **Item** class extends `\ArrayObject` and implements *ItemInterface*.

```php
<?php
use Germania\OrderDispatcher\ItemInterface;
use Germania\OrderDispatcher\Item;
```



#### ItemFactoryInterface

```php
public function createItem( array $order_item ) : ItemInterface;
```



#### SimpleItemFactory

Create an “order item” array based on any array data. Extends **ItemFactoryAbstract** and implements **ItemFactoryInterface**.

```php
<?php
use Germania\OrderDispatcher\SimpleItemFactory;

$item_factory = new SimpleItemFactory;

$item = $item_factory->createItem([
  'sku' => 'foobar',
  'quantity' => 100
]);
```



#### ContainerItemFactory

Use this to restrict order items to only items from a PSR-11 `Psr\Container\ContainerInterface`. The array field name with which the item shall be retrieved is required, and the constructor optionally accepts a PSR-3 Logger. 

Extends **ItemFactoryAbstract** and implements **ItemFactoryInterface**.

When an item is not available, a `ItemNotAvailableException` will be thrown.

```php
<?php
use Germania\OrderDispatcher\ContainerItemFactory;
use Germania\OrderDispatcher\Exceptions\ItemNotAvailableException;

$available = new Psr11Container( ... );

$item_factory = new ContainerItemFactory($available, "sku");
$item_factory = new ContainerItemFactory($available, "sku", $logger);

$sku = 'foobar';

try {
  $item = $item_factory->createItem([
    'sku' => $sku,
    'quantity' => 100
  ]);  
}
catch (ItemNotAvailableException $e) {
  echo "$sku is not available";
}

```



#### ValidatorItemFactoryDecorator

This decorator accepts any *ItemFactoryInterface* and a *ValidatorInterface* to validate the order item data.

```php
<?php
use Germania\OrderDispatcher\ValidatorItemFactoryDecorator;
use Germania\OrderDispatcher\ContainerItemFactory;

$inner = new ContainerItemFactory($available, "sku");
$validator = new SkuQtyItemValidator;

$item_factory = ValidatorItemFactoryDecorator($inner, $validator);
```





---



### Render orders

Renderers are used to create a string representation from an order object, typically for emails.



#### RendererInterface

Renderers accept a *template* string and a context variables array. In case of errors, `Germania\OrderDispatcher\Exceptions\RendererExceptionInterface` must be thrown.

```php
public function render( string $template, array $context = array()) : ?string;
```



#### TwigRenderer

The *TwigRenderer* implements the *RendererInterface.* Its constructor accepts a Twig environment object, and optionally an array with default context variables.

```php
<?php
use Germania\OrderDispatcher\TwigRenderer;

$twig = ... ;

$renderer = new TwigRenderer($twig);
```



---



### Work with orders



#### OrderHandlerInterface

```php
public function handle( OrderInterface $order, array $context = array()) : bool ;
```



#### SwiftMailerOrderHandler

The *SwiftMailerOrderHandler* implements the *OrderHandlerInterface*. Its constructor accepts a SwiftMailer instance, a mail configuration array, and a *RendererInterface* instance.

Typically, you will use the *TwigRenderer* for RendererInterface.

The mail configuration array must contain a `to `, `from`, `template,`and a `subject` element. The subject may have field variables in curly braces which are interpolated from the handle method context.

*Subject* and *template* given in mail configuration array may be overridden by  `mailSubject` or `mailTemplate` entry in the context array.

The renderer will be passed the handler context with additional `customer`, `orderItems`, and `datetimeNow` information.

```php
<?php
use Germania\OrderDispatcher\SwiftMailerOrderHandler;

$order = ...;
$renderer = ... ;
$swift_mailer = ...;

$mail_config = array(
	'to' => array("mail@test.com" => "John Doe"),
  'from' => array("webshop@test.com" => "My Webshop"),
  'template' => 'mail.tpl',
  'subject' => "{foo} {bar}"
);
$handler = new SwiftMailerOrderHandler($swift_mailer, $mail_config, $renderer);

$handler->handle($order, [
  'foo' => "Order"
  'bar' => "beverage"
]);
```



#### OrderHandlerChain

Use this handler to mangle an order through multiple handlers.

```php
<?php
use Germania\OrderDispatcher\OrderHandlerChain;

$handlers = array();
$chain = new OrderHandlerChain($handlers);

$handler2 = new ...;
$chain->add( $handler2 );

```



### Validators

#### ValidatorInterface

Validates user input and returns cleaned data.  Usually PHP's `filter_var_array` will be used.

When writing your own implementation, missing fields *should* be added and set to `null`, any additional content *should* be merged back.

```php
public function validate( array $input ): array;
```



#### ValidatorTrait

Interceptors for *ValidatorInterface*.

```php
protected $validator;
public function setValidator( ValidatorInterface $validator );
public function getValidator() : ValidatorInterface;
```



#### FilterValidator

This validator checks user input with PHP's `filter_var_array`. 

- Missing fields will be added and set to `null`
- Additional “unvalidated” content will be merged back, as opposite to original *filter_var_array*.

The *FilterValidator* Implements *ValidatorInterface*.

```php
<?php
use Germania\OrderDispatcher\FilterValidator;

$fv = new FilterValidator([
  "email"  =>  FILTER_VALIDATE_EMAIL,
  "company" =>  FILTER_SANITIZE_FULL_SPECIAL_CHARS  
]);

$result = $fv->validate([
  'email' => 9999,
  'company' => 'ACME Corp.',
  'foo' => 'bar'
]);

print_r($result);
// Array
// (
//     [email] => false
//     [company] => ACME Corp.
//     [foo] => bar
// )
```



#### SkuQtyItemValidator

This is a predefined Validator which checks user input for `sku` and `quantity` elements. It works just like **FilterValidator:**

- Missing fields will be added and set to `null`
- Additional “unvalidated” content will be merged back, as opposite to original *filter_var_array*.

###### Exceptions

- **ItemInvalidUserDataException**, when `sku` or `quantity` missing or *false*
- **ItemNotOrderedException**, when `quantity` is *int 0*.

The *SkuQtyItemValidator* Implements *ValidatorInterface*.

```php
<?php
use Germania\OrderDispatcher\SkuQtyItemValidator;
use Germania\OrderDispatcher\Exceptions\ItemInvalidUserDataException;
use Germania\OrderDispatcher\Exceptions\ItemNotOrderedException;

$v = new SkuQtyItemValidator();

try {
  $v->validate([
    'sku' => 'foo',
    'quantity' => 100
  ]);
}
catch(ItemInvalidUserDataException $e) {
  
}
catch(ItemNotOrderedException $e) {
  	// quantity was 0 (!== null)
}
```





## Exceptions

| Interface                      | sub interface          | Class                             | parent                   |
| ------------------------------ | ---------------------- | --------------------------------- | ------------------------ |
| OrderFactoryExceptionInterface |                        | ItemNotAvailableException         | UnexpectedValueException |
| OrderFactoryExceptionInterface |                        | RequiredUserDataMissingException  | UnexpectedValueException |
| OrderFactoryExceptionInterface |                        | NoArticlesOrderedException        | Exception                |
| OrderHandlerExceptionInterface |                        | OrderHandlerRuntimeException      | RuntimeException         |
| OrderHandlerExceptionInterface | ItemExceptionInterface | ItemInvalidUserDataException      | UnexpectedValueException |
| OrderHandlerExceptionInterface | ItemExceptionInterface | ItemNotOrderedException           | Exception                |
| RendererExceptionInterface     |                        | RendererRuntimeException          | RuntimeException         |
| ValidatorExceptionInterface    |                        | ValidatorUnexpectedValueException | UnexpectedValueException |

