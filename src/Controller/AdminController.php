<?php

namespace LpDigital\Bundle\LdapBundle\Controller;

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

use BackBee\Bundle\AbstractAdminBundleController;
use BackBee\Security\Group;

/**
 * Bundle administration controller.
 *
 * @copyright    ©2017 - Lp digital
 * @author       Charles Rouillon <charles.rouillon@lp-digital.fr>
 */
class AdminController extends AbstractAdminBundleController
{
    /**
     * Controller entry point.
     * Displays the LDAP configuration for the current site.
     *
     * @return string Index template rendering.
     */
    public function indexAction()
    {
        try {
            $this->isGranted('VIEW', $this->getBundle());

            return $this->application->getRenderer()->partial(
                'LdapBundle/Admin/Index.html.twig',
                [
                    'bundle' => $this->getBundle(),
                    'groups' => $this->application->getEntityManager()->getRepository(Group::class)->findBy([], ['_name' => 'asc'])
                ]
            );
        } catch (\Exception $ex) {
            $this->notifyUser(self::NOTIFY_ERROR, $ex->getMessage());
        }
    }

    /**
     * Saves the bundle configuration.
     *
     * @return string
     */
    public function saveAction()
    {
        try {
            $request = $this->getRequest()->request;

            $parameters = [
                'persist_on_missing' => 'true' === $request->get('persist_on_missing', 'false'),
                'store_attributes' => array_filter(array_map('trim', explode(',', $request->get('store_attributes', '')))),
                'default_backbee_groups' => $request->get('default_backbee_groups[]', []),
            ];

            $names = $request->get('server_name[]', []);
            $hosts = $request->get('server_options_host[]', []);
            $ports = $request->get('server_options_port[]', []);
            $versions = $request->get('server_options_version[]', []);
            $encryptions = $request->get('server_options_encryption[]', []);
            $searchDns = $request->get('server_search_dn[]', []);
            $searchPassword = $request->get('server_search_password[]', []);
            $baseDns = $request->get('server_base_dn[]', []);
            $filters = $request->get('server_filter[]', []);

            $index = 0;
            $servers = [];
            foreach ($names as $name) {
                if (!isset($hosts[$index]) || empty($hosts[$index])) {
                    continue;
                }

                $servers[$name] = [
                    'options' => [
                        'host' => $hosts[$index],
                        'port' => isset($ports[$index]) && !empty($ports[$index]) ? $ports[$index] : 389,
                        'version' => isset($versions[$index]) && !empty($versions[$index]) ? $versions[$index] : 3,
                        'encryption' => isset($encryptions[$index]) && !empty($encryptions[$index]) ? $encryptions[$index] : 'none',
                    ],
                    'search_dn' => isset($searchDns[$index]) ? $searchDns[$index] : '',
                    'search_password' => isset($searchPassword[$index]) ? $searchPassword[$index] : '',
                    'base_dn' => isset($baseDns[$index]) ? $baseDns[$index] : '',
                    'filter' => isset($filters[$index]) ? $filters[$index] : '',
                ];

                $index++;
            }

            $bundle = $this->getBundle();
            $bundle->getConfig()->setSection('parameters', $parameters, true);
            $bundle->getConfig()->setSection('ldap', $servers, true);

            $bundleConfig = $bundle->getConfig()->getBundleConfig();
            $this->getContainer()->get('config.persistor')->persist(
                    $bundle->getConfig(), isset($bundleConfig['config_per_site']) ? $bundleConfig['config_per_site'] : false
            );

            $bundle->start();

            $this->notifyUser(self::NOTIFY_SUCCESS, 'Configuration saved.');
        } catch (\Exception $ex) {
            $this->notifyUser(self::NOTIFY_ERROR, 'Configuration not saved: ' . $ex->getMessage());
        }

        return $this->indexAction();
    }
}
