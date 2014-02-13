jsonize
=======

This extension is a simple to use function for json encoding CActiveRecord and their relations

##Requirements

- PHP 5.2 or above
- Yii 1.1 or above

##Installation

- copy jsonize.php into your protected/components folder
- add the following line to your config/main.php: 
~~~php
'components' => array(
    ...
    'jsonize'=>array('class'=>'jsonize'),
    ...
),
~~~
- optionally preload the component:
~~~php
'preload' => array( ..., 'jsonize'),
~~~

##Usage

~~~php
Yii::app()->jsonize; // load the component (not needed if it is preloaded)

$users = User::model()->with('votes')->findAll($criteria);

echo jsonizenc($users); // all attributes and loaded relations ('votes' in this example) are processed
$array = jsonize($users); // same as before, but returns an array of attributes/relations, not a JSON-encoded string

echo jsonizenc($users, array('posts','comments')); // all attributes are processed, specified relations and loaded ones ('posts','comments' and 'votes') are processed

echo jsonizenc($users, array('posts','comments'), true); // all attributes are processed, but only specified relations ('posts','comments') are processed

echo jsonizenc($users, array('id', 'username', 'email', 'first_name', 'last_name', 
    'posts' => array( 'id', 'author_id', 'content' ),
    'comments' => array( 'id', 'author_id', 'content' ),
    'votes' => array( /* attributes here */))
), true, true); // only specified attributes and relations are processed
~~~

#Processing nested relations

By default, all attributes (returned by getAttributes()) and all first-level LOADED relations are processed. Nested relations have to be loaded explicitly:

~~~php
echo jsonizenc($users, array('posts'=>array('comments'))); 
// output {
	"id":"1",
	"name":"David",
	"posts":[
		{
			"id":"1",
			"user_id":"1",
			"title":"new post",
			"comments": [{
				"id": 1,
				"post_id": "1"
				"text": "Nice post"
			}]
		},{
			"id":"2",
			"user_id":"1",
			"title":"other post",
			"comments": [{
				"id": "2",
				"post_id": "2",
				"text": "comment other post"
			},{
				"id": "3",
				"post_id: "2",
				"text": "other comment other post"
			}]
		}
	]
}
~~~
There's no limit to this notation, you could do:
~~~php
echo jsonizenc($users, array('posts'=>array('user'=>array('posts','comments'))));
// it will output also $user->posts, $user->posts->user, $user->posts->user->posts and $user->posts->user->comments
~~~
##Notes

For nested relations, the policy specifying which attributes/relations are to be processed is the same as the first one. For example:
~~~php
echo jsonizenc($users, array('id','posts'=>array('title','user'=>array('name')), true, true)); 
// only specified attributes and relations of user, user.posts and user.posts.user will be processed
~~~

##API

~~~php
/**
 * @param mixed $data an ActiveRecord or array of ActiveRecord
 * @param $attributes array of attributes/relations to be processed e.g ['client','items' => ['product']] // nested relations
 * @param bool $onlySpecifiedRelations if to send all loaded relations or only the ones specified in $attributes 
 * @param bool $onlySpecifiedAttributes if to send all attributes or only the ones specified in $attributes 
 */
function jsonize($data, $attributes=true, $onlySpecifiedRelations = false, $onlySpecifiedAttributes = false);

/**
 * shortcut for json_encode(jsonize($data))
 * @see jsonize()
 */ 
function jsonizenc($data, $attributes=true, $onlySpecifiedRelations = false, $onlySpecifiedAttributes = false);
}

~~~
