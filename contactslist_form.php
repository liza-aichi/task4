<?php

/**
 * contacts list form
 *
 */
function list_form($form, &$form_state)
{
    global $user;

    $header = array(

       'full_name'=>t('Name'),
       //'name'=>t('Category'),
     'name'=>array('data' => t('Category'),'field'=>'2'),
        t('Email'),
        t('Phone'),
        t('Birthday'),
        t(''),
    );

    $rows = array();
    $options = array();
    $result = db_select('contact', 'c')->extend('PagerDefault');
    $result->extend('TableSort')->orderByHeader($header);
    $result->join('taxonomy_term_data', 't', 't.tid = c.category_id');
    $result->fields('t', array('tid', 'name'));
    $result->fields('c', array('contact_id', 'user_id', 'first_name', 'last_name', 'email', 'phone', 'birthday', 'category_id'));
    $result->condition('user_id', $user->uid, '=');

    $result_rows = $result->execute();

    $names = array();

    foreach ($result_rows as $row) {
        $options[$row->contact_id] = array(
           'full_name'=>'<a href="/address-book/' . $row->contact_id . '/contact">' . $row->first_name . ' ' . $row->last_name . '</a>',
            'name' => $row->name,
            $row->email,
            $row->phone,
            $date = (new DateTime($row->birthday))->format('d/m/Y'),
            '<a href="/address-book/delete/' . $row->contact_id . '"> Delete </a> '.'<a href="/address-book/' . $row->contact_id .'/edit "> Edit </a> '
        );
        $names[$row->contact_id] = $row->first_name . ' ' . $row->last_name;
    }
    $vocabulary = taxonomy_vocabulary_machine_name_load('contact_category');
    $terms = taxonomy_get_tree($vocabulary->vid);
    $category = array();
    foreach($terms as $term) {
        $category[$term->name] = $term->name;}
    $form['changecat'] = array(
        '#type' => 'fieldset',
        '#attributes' => array('class' => array('container-inline')),

    );
    $form['changecat']['category'] = array(
        '#type' => 'select',
        '#title' => t('Change category of selected users'),
        '#name'=>'category',
        '#options' => $category

    );
    $form['changecat']['apply'] = array(
        '#type' => 'submit',
        '#value' => t('Apply'),
        '#submit' => array('apply_listform'),
      //  '#suffix' => '</div>',
    );
    $form['table'] = array(
        '#type' => 'tableselect',
        '#header' => $header,
        '#options' => $options,
        '#empty' => t('No contacts found'),
      //  '#markup' =>  theme('pager'),
    );//.theme("pager");
   $form['pager'] = array('#markup' => theme('pager'));
    $form['names'] = array(
        '#type' => 'value',
        '#value' => $names
    );
    $form['submit'] = array(
        '#type' => 'submit',
        '#value' => t('Delete'),
       // '#weight' => -50,
        '#submit' => array('list_form_submit'),
    );

    if (isset($_SESSION['args'])) unset($_SESSION['args']);

    return $form;
}

/**
 *contacts list form submit
 * @param $form
 * @param $form_state
 */
function list_form_submit(&$form, &$form_state)
{
    $results = array_filter($form_state['values']['table']);
    $args;
    foreach ($results as $name)
        $args[$name] = $form_state['values']['names'][$name];

    if (isset($args)) $_SESSION['args'] = $args;
    drupal_goto('address-book/delete');

}
/***
 *submit button for changing category of selected contacts
 *
 * @param $form
 * @param $form_state
 */
function apply_listform(&$form, &$form_state)
{
    $results = array_filter($form_state['values']['table']);
  if(count($results)>0){
    $category=$form_state['values']['category'];
    $result = db_select('taxonomy_term_data', 'addr')
        ->fields('addr',array('tid','name'))
        ->condition('name', $category,'=')
        ->execute()
        ->fetchAssoc();
    $tid=$result['tid'];
    foreach($results as $res)
    {
        $num_updated = db_update('contact') // Table name no longer needs {}
            ->fields(array(
                'category_id' => $tid,

            ))
            ->condition('contact_id', $res, '=')
            ->execute();
    }}

}
