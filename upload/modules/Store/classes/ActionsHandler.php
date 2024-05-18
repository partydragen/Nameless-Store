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

    public function registerPlaceholders() {

    }

    public function registerCondition(Condition $condition) {

    }
}