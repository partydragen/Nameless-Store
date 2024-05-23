<?php
class ActionsHandler extends Instanceable {

    /**
     * @var Action[] The list of actions.
     */
    private array $_actions;

    /**
     * @var Action[] The list of product actions.
     */
    private array $_product_actions;

    private array $_placeholders_injectors = [];

    private array $_placeholders = [];

    /**
     * Get the global or product actions.
     *
     * @param Product|null $product
     * @param string|null $trigger Trigger type like Purchase/Refund/Changeback etc
     *
     * @return array Actions list.
     */
    public function getActions(?Product $product = null, string $trigger = null): array {
        if ($product == null) {
            $actions = $this->_actions ??= (function (): array {
                $this->_actions = [];

                $actions_query = DB::getInstance()->query('SELECT * FROM nl2_store_products_actions WHERE product_id IS NULL ORDER BY `order` ASC');
                if ($actions_query->count()) {
                    $services = Services::getInstance();

                    foreach ($actions_query->results() as $data) {
                        $service = $services->get($data->service_id);
                        if ($service == null) {
                            continue;
                        }

                        $action = new Action($service, null, null, $data);

                        $this->_actions[$action->data()->id] = $action;
                    }
                }

                return $this->_actions;
            })();
        } else {
            $actions = $this->_product_actions[$product->data()->id] ??= (function ($product): array {
                $this->_product_actions[$product->data()->id] = [];

                $actions_query = DB::getInstance()->query('SELECT * FROM nl2_store_products_actions WHERE product_id = ? OR product_id IS NULL ORDER BY `order` ASC', [$product->data()->id]);
                if ($actions_query->count()) {
                    $services = Services::getInstance();

                    foreach ($actions_query->results() as $data) {
                        $service = $services->get($data->service_id);
                        if ($service == null) {
                            continue;
                        }

                        $action = new Action($service, null, null, $data);

                        $this->_product_actions[$product->data()->id][$action->data()->id] = $action;
                    }
                }

                return $this->_product_actions[$product->data()->id];
            })($product);
        }

        if ($trigger) {
            $return = [];
            foreach ($actions as $action) {
                if ($action->data()->type == $trigger) {
                    $return[$action->data()->id] = $action;
                }
            }

            return $return;
        }

        return $actions;
    }

    /**
     * Get action by id.
     *
     * @param int $id Action id
     *
     * @return Action|null Action by id otherwise null.
     */
    public function getAction(int $id): ?Action {
        $actions_query = DB::getInstance()->query('SELECT * FROM nl2_store_products_actions WHERE id = ?', [$id]);
        if ($actions_query->count()) {
            $data = $actions_query->first();

            $service = Services::getInstance()->get($data->service_id);
            if ($service == null) {
                return null;
            }

            return new Action($service, null, null, $data);
        }

        return null;
    }

    /**
     * Register placeholder injector for actions execution.
     *
     * @param string $module The name of the module that registered the transformer.
     * @param Closure(Order, Item, Payment): mixed $placeholders_injector Function which converts the placeholder to the desired type.
     */
    public function registerPlaceholders(string $module, Closure $placeholders_injector) {
        $reflection = new ReflectionFunction($placeholders_injector);
        $reflectionParams = $reflection->getParameters();
        if (count($reflectionParams) !== 3) {
            throw new InvalidArgumentException('Placeholders injector must take 3 arguments (Order, Item and Payment).');
        }

        // Check that the first argument is Order class
        $param = $reflectionParams[0];
        if ($param->getType() instanceof ReflectionNamedType && $param->getType()->getName() !== Order::class) {
            throw new InvalidArgumentException('Placeholders injector must take Order as the first argument.');
        }

        // Check that the second argument is Item class
        $param = $reflectionParams[1];
        if ($param->getType() instanceof ReflectionNamedType && $param->getType()->getName() !== Item::class) {
            throw new InvalidArgumentException('Placeholders injector must take Item as the second argument.');
        }

        // Check that the third argument is Payment class
        $param = $reflectionParams[2];
        if ($param->getType() instanceof ReflectionNamedType && $param->getType()->getName() !== Payment::class) {
            throw new InvalidArgumentException('Placeholders injector must take Payment as the third argument.');
        }

        $this->_placeholders_injectors[] = [
            'module' => $module,
            'injector' => $placeholders_injector,
        ];
    }

    public function getPlaceholders(Action $action, Order $order, Item $item, Payment $payment): array {
        $key = $action->data()->id . '-' . $item->getId();

        return $this->_placeholders[$key] ??= (function (Action $action, Order $order, Item $item, Payment $payment): array {
            $placeholders = [];

            foreach ($this->_placeholders_injectors as $injector) {
                try {
                    $placeholders = array_merge($placeholders, $injector['injector']($order, $item, $payment));
                } catch (Exception $e) {

                }
            }

            return $placeholders;
        })($action, $order, $item, $payment);
    }

    public function getPlaceholder(string $placeholder, Action $action, Order $order, Item $item, Payment $payment) {
        $placeholders = $this->getPlaceholders($action, $order, $item, $payment);
        if (array_key_exists($placeholder, $placeholders)) {
            return $placeholders[$placeholder];
        }

        return null;
    }

    public function registerCondition(Condition $condition) {

    }
}