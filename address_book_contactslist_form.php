<?php

/**
 * contacts list form
 *
 */
function address_book_list_form($form, &$form_state)
{

    if (!empty($form_state['values']['op']) && $form_state['values']['op'] == t('Delete')) {
        return address_book_delete_confirm($form, $form_state);
    }
    global $user;

    $header = array(
t('Photo'),
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
  //  $result->join('file_managed', 'f', 'f.fid = c.img_id');
    $result->fields('t', array('tid', 'name'));
   // $result->fields('f', array('fid', 'uri'));
    $result->fields('c', array('contact_id', 'user_id', 'first_name', 'last_name', 'email', 'phone', 'birthday', 'category_id','img_id'));
    $result->condition('user_id', $user->uid, '=');

    $result_rows = $result->execute();

    $names = array();

    foreach ($result_rows as $row) {
        if($row->img_id!=''){
            $photo = db_select('file_managed', 'm');
          //  $result->extend('TableSort')->orderByHeader($header);
         //   $result->join('taxonomy_term_data', 't', 't.tid = c.category_id');
          //  $result->join('file_managed', 'f', 'f.fid = c.img_id');
            $photo->fields('m', array('fid', 'uri'));
            // $result->fields('f', array('fid', 'uri'));
          //  $result->fields('c', array('contact_id', 'user_id', 'first_name', 'last_name', 'email', 'phone', 'birthday', 'category_id'));
            $photo->condition('fid',$row->img_id , '=');
            $photo_row = $photo->execute()->fetchAll();

          $image_options = array(
                'style_name' => 'contact_preview_img',
                'path' => $photo_row[0]->uri,

            );
            $row->img_id=theme('image_style', $image_options);
        }
        $options[$row->contact_id] = array(
            $row->img_id,
           'full_name'=>'<a href="/address-book/contact/' . strtolower($row->first_name).'-'.strtolower($row->last_name). '">' . $row->first_name . ' ' . $row->last_name . '</a>',
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
        '#submit' => array('address_book_apply_listform'),
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
        '#submit' => array('address_book_list_form_submit'),
    );



    return $form;
}

function address_book_list_form_validate($form, &$form_state) {
    if (!empty($form_state['values']['op']) && $form_state['values']['op'] == t('Delete')) {
        $form_state['rebuild'] = TRUE;
    }
}
/***
 * delete confirm form
 * @param $form
 * @param $form_state
 * @return mixed
 */
function address_book_delete_confirm($form, & $form_state) {

   $form['#submit'][] = 'address_book_delete_confirm_submit';
    $form_state['storage']['table'] = $form_state['values']['table'];
    $question = t("Confirm deleting this contatcs?");
    $path = 'address-book';

    $description = '<ul>';


    foreach ( $form_state['values']['table'] as $contact) {
        if($contact!='') { $result = db_select('contact', 'c');
        $result->fields('c', array('contact_id', 'first_name','last_name'));
        $result->condition('contact_id', $contact,'=');
        $result_rows = $result->execute()->fetchAll();

       $description .= '<li>' . $result_rows[0]->first_name .' '.
           $result_rows[0]->last_name.'</li>';

       // $ids[$key] = $key;
    }

    }
if($description=='<ul>') drupal_goto('address-book');
    $description .= '</ul>';

    return confirm_form($form, $question, $path, $description);//,// $yes, $no);//, $name);
 //   return confirm_form($form, t('Are you sure?'), 'address-book');

}
/***
 * delete contacts confirm submit
 * @param $form
 * @param $form_state
 */
function address_book_delete_confirm_submit($form, & $form_state) {

    drupal_set_message(t('Contacts were deleted'));

    $orid = db_or();
    foreach ($form_state['storage']['table'] as $id)
    {if($id!=''){
        $result = db_select('contact', 'c');
        $result->join('file_managed', 'f', 'f.fid = c.img_id');
        $result->fields('c', array('contact_id', 'img_id'));
        $result->fields('f', array('fid', 'uri'));

        $result->condition('contact_id', $id, '=');
        $result_rows = $result->execute()->fetchAll();
        if (isset($result_rows[0])) {
            $or = db_or();
            $or->condition('fid', $result_rows[0]->img_id, '=');
            $file = file_load($result_rows[0]->fid);

            file_unmanaged_delete($file->uri);
        }
        $orid->condition('contact_id', $id, '=');
    }
    }

    if (isset($or)) {
        $result = db_delete('file_managed');
        $result->condition($or)->execute();
    }
    db_delete('contact')->condition($orid)->execute();
    //dsm($form_state);//['storage']);
}


/***
 *submit button for changing category of selected contacts
 *
 * @param $form
 * @param $form_state
 */
function address_book_apply_listform(&$form, &$form_state)
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
