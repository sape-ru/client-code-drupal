<?php

namespace Drupal\sape\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Form\FormStateInterface;
/**
 * Provides a 'Hello' Block.
 *
 * @Block(
 *   id = "rtb_block",
 *   admin_label = @Translation("Sape RTB"),
 * )
 */
class RtbBlock extends BlockBase {

    /**
     * {@inheritdoc}
     */
    public function build() {
        $config = $this->getConfiguration();

        $data = $config['content'];
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

    /**
     * {@inheritdoc}
     */
    public function blockForm($form, FormStateInterface $form_state) {
        $form = parent::blockForm($form, $form_state);

        $config = $this->getConfiguration();


        $form['content'] = array(
            '#type' => 'textarea',
            '#title' => $this->t('Код RTB блока'),
            '#description' => $this->t('Код RTB блока, полученный для тела страницы'),
            '#default_value' => isset($config['content']) ? $config['content'] : '',
        );

        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function blockSubmit($form, FormStateInterface $form_state) {
        $this->configuration['content'] = $form_state->getValue('content');

        /*
         * $this->setConfigurationValue('loremipsum_block_settings', $form_state->getValue('loremipsum_block_settings'));
         *
         * */
    }
}