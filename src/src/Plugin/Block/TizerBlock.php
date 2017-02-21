<?php

namespace Drupal\sape\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\sape\vendor\sape;
/**
 * Provides a 'Hello' Block.
 *
 * @Block(
 *   id = "tizer_block",
 *   admin_label = @Translation("Sape Tizer"),
 * )
 */
class TizerBlock extends BlockBase {

    /**
     * {@inheritdoc}
     */
    public function build() {
        $default_config = \Drupal::config('sape.settings');
        $config = $this->getConfiguration();
        if($default_config->get('sape.USER_ID') && ($default_config->get('sape.tizers'))){

            if(!defined('_SAPE_USER')) {
                define('_SAPE_USER', $default_config->get('sape.USER_ID'));
            }
            $sape = \Drupal\sape\vendor\sape\SAPE_client::getInstance( array(
                    'charset'                 => 'UTF-8',
                    'multi_site'              => true,
                    'show_counter_separately' => true,
                    'force_show_code' => $default_config->get('sape.debug')
                )
            );

            if(!defined('_SAPE_COUNTER')) {
                define('_SAPE_COUNTER', $sape->return_counter());
            }

            $data = $sape->return_teasers_block( $config['count']);
            if($data){





                return array(
                    '#type' => 'HtmlTag',
                    '#tag'=>'div',
                    '#markup' => 'data',
                    '#post_render'=>array(function () use($data){
                        return $data;
                    }),
                    '#cache' => array('max-age'=>0)

                );

            }



        }

        return array(
            '#markup' => $config['content'],
            '#cache' => array('max-age'=>0)
        );
    }


    /**
     * {@inheritdoc}
     */
    public function blockForm($form, FormStateInterface $form_state) {
        $form = parent::blockForm($form, $form_state);

        $config = $this->getConfiguration();

        $form['count'] = array(
            '#type' => 'textfield',
            '#title' => $this->t('ID тизерного блока'),
            '#description' => $this->t('ID тизерного блока из системы'),
            '#default_value' => isset($config['count']) ? $config['count'] : '',
        );

        $form['content'] = array(
            '#type' => 'textfield',
            '#title' => $this->t('Альтернативный текст'),
            '#description' => $this->t('Укажите альтернативный текст, выводимый, если нет тизеров'),
            '#default_value' => isset($config['content']) ? $config['content'] : '',
        );

        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function blockSubmit($form, FormStateInterface $form_state) {
        $this->configuration['count'] = $form_state->getValue('count');
        $this->configuration['content'] = $form_state->getValue('content');

        /*
         * $this->setConfigurationValue('loremipsum_block_settings', $form_state->getValue('loremipsum_block_settings'));
         *
         * */
    }
}