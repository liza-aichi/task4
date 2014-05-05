<?php


/**
 * add_contact_form used for adding and editing contacts
 *
 */
function address_book_add_form($form, &$form_state, $arg = NULL)
{
    if ($arg != '') {

        $result = db_select('contact', 'c');
        $result->join('file_managed', 'f', 'f.fid = c.img_id');
        $result->join('taxonomy_term_data', 't', 't.tid = c.category_id');
        $result->fields('c', array('contact_id', 'first_name', 'last_name',
            'email', 'phone', 'birthday', 'notes', 'category_id'));
        $result->fields('f', array('fid', 'uri'));
        $result->fields('t', array('tid', 'name'));

        $result->condition('contact_id', $arg, '=');
        $result_rows = $result->execute()->fetchAll();
        if (count($result_rows) > 0) {
            $image_options = array(
                'style_name' => 'contact_img',
                'path' => $result_rows[0]->uri,

            );

            $image = theme('image_style', $image_options);

            $form['image'] = array(
                '#markup' => $image,
            );
            $photo_title = t('Change photo:');

        } else {
            $result = db_select('contact', 'c');
            $result->join('taxonomy_term_data', 't', 't.tid = c.category_id');
            $result->fields('c', array('contact_id', 'first_name', 'last_name',
                'email', 'phone', 'birthday', 'notes', 'category_id'));
            $result->fields('t', array('tid', 'name'));
            $result->condition('contact_id', $arg, '=');
            $result_rows = $result->execute()->fetchAll();
            $photo_title = t('Add photo:');

        }
        $form['id'] = array(
            '#type' => 'value',
            '#value' => $arg
        );

    } else {
        $photo_title = t('Photo:');

    }


    $form['photo_image_fid'] = array(
        '#title' => $photo_title,
        '#type' => 'managed_file',
        //  '#description' => t('The uploaded image will be displayed on this page using the image style choosen below.'),
        '#default_value' => variable_get('photo_image_fid', ''),
        '#upload_location' => 'public://addressbook_files',
        '#upload_validators' => array(
            'file_validate_extensions' => array('gif png jpg jpeg'),
            'file_validate_size' => array(1 * 300 * 300),
        ),


    );
    $form['first_name'] = array(
        '#type' => 'textfield',
        '#title' => t('First name:'),


    );
    if (isset($result_rows[0]))
        $form['first_name']['#default_value'] = $result_rows[0]->first_name;
    $form['last_name'] = array(
        '#type' => 'textfield',
        '#title' => t('Last name:')

    );
    if (isset($result_rows[0]))
        $form['last_name']['#default_value'] = $result_rows[0]->last_name;
    $form['email'] = array(
        '#type' => 'textfield',
        '#title' => t('Email:')

    );
    if (isset($result_rows[0]))
        $form['email']['#default_value'] = $result_rows[0]->email;
    $form['phone'] = array(
        '#type' => 'textfield',
        '#title' => t('Phone:')

    );
    if (isset($result_rows[0]))
        $form['phone']['#default_value'] = $result_rows[0]->phone;

    $form['birthday'] = array(
        '#type' => 'date',
        '#title' => 'Birthday:',

    );
    if (isset($result_rows[0])) {
        $mmdate = '2008-12-31 00:00:00';
        $mdate = new DateTime($result_rows[0]->birthday);
        //  $form['birthday']['#value']['year'] = $mdate->format('Y');
        $form['birthday']['#default_value']['month'] = $mdate->format('n');
        $form['birthday']['#default_value']['day'] = $mdate->format('d');
        $form['birthday']['#default_value']['year'] = $mdate->format('Y');
    }
    $vocabulary = taxonomy_vocabulary_machine_name_load('contact_category');
    $terms = taxonomy_get_tree($vocabulary->vid);
    $category = array();
    foreach ($terms as $term) {
        $category[$term->name] = $term->name;
    }


    $form['category'] = array(
        '#type' => 'select',
        '#title' => t('Category'),
        '#name' => 'category',
        '#options' => $category

    );
    if (isset($result_rows[0]))
        $form['category']['#default_value'] = $result_rows[0]->name;

    $form['Note'] = array(
        '#title' => t('Notes:'),
        '#type' => 'textarea',

    );
    if (isset($result_rows[0]))
        $form['Note']['#default_value'] = $result_rows[0]->notes;

    $form['submit'] = array(
        '#type' => 'submit',
        '#value' => t('Create'),
    );
    if (isset($result_rows[0]))
        $form['submit']['#value'] = t('Edit');

    return $form;
}


/***
 *
 * add and edit contact form submit
 * @param $form
 * @param $form_state
 */
function address_book_add_form_submit(&$form, &$form_state)
{
    global $user; // dsm($form_state);
    $file = file_load($form_state['values']['photo_image_fid']);
    $notempty = ($file != NULL);
    $fileid = NULL;
    if (isset($form_state['complete form']['image']) && $notempty) {
        //del file

        $result = db_select('contact', 'c');
        $result->join('file_managed', 'f', 'f.fid = c.img_id');
        $result->fields('c', array('contact_id', 'img_id'));
        $result->fields('f', array('fid', 'uri'));

        $result->condition('contact_id', $form_state['values']['id'], '=');
        $result_rows = $result->execute()->fetchAll();
        $oldfile = file_load($result_rows[0]->fid);
        file_unmanaged_delete($oldfile->uri);
    }


    if ($notempty) {
        $file->status = FILE_STATUS_PERMANENT;
        file_save($file);
        $fileid = $file->fid;
    }
    $date_arr = $form_state['values']['birthday'];
    $date = new DateObject($date_arr);

    $category = $form_state['values']['category'];
    $result = db_select('taxonomy_term_data', 'addr')
        ->fields('addr', array('tid', 'name'))
        ->condition('name', $category, '=')
        ->execute()
        ->fetchAssoc();
    $tid = $result['tid'];
    $myfields = array(
        'first_name' => $form_state['values']['first_name'],
        'last_name' => $form_state['values']['last_name'],
        'email' => $form_state['values']['email'],
        'phone' => $form_state['values']['phone'],
        'notes' => substr($form_state['values']['Note'], 0, 250),
        'birthday' => date_format($date, DATE_FORMAT_DATETIME),
        'category_id' => $tid,
        'user_id' => $user->uid);
    if ($notempty) $myfields['img_id'] = $fileid;
    if (isset($form_state['values']['id'])) { //dsm($form_state['values']['id']);

        drupal_set_message(
            t(
                'Contact @first_name @last_name was changed', array(
                '@first_name' => $form_state['values']['first_name'],
                '@last_name' => $form_state['values']['last_name']
            ))
        );
        $id = db_update('contact')
            ->fields($myfields)
            ->condition('contact_id', $form_state['values']['id'], '=')
            ->execute();
        $path['source'] = 'address-book/'.$form_state['values']['id'].'/contact';
        $path['alias'] = 'address-book/contact/'. $form_state['values']['first_name'].'-'.$form_state['values']['last_name'];
        path_save($path);
    } else {
        drupal_set_message(
            t(
                'Contact @first_name @last_name was added', array(
                '@first_name' => $form_state['values']['first_name'],
                '@last_name' => $form_state['values']['last_name']
            ))
        );
        $id = db_insert('contact')
            ->fields($myfields)
            ->execute();
        $path['source'] = 'address-book/'.$id.'/contact';
        $path['alias'] = 'address-book/contact/'. $form_state['values']['first_name'].'-'.$form_state['values']['last_name'];
        path_save($path);

    }
    if ($notempty) file_usage_add($file, 'address_book', 'contact', $id);

}
