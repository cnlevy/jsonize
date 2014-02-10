jsonize
=======

This extension is a simple to use function for json encoding CActiveRecord and their relations

##Requirements

- PHP 5.2 or above
- Yii 1.1 or above

##Installation

- copy jsonize.php into your protected/components folder
- add the following line to your config/main.php: 
~~~
'components' => array(
    ...
    'jsonize'=>array('class'=>'jsonize'),
    ...
),
~~~
- optionally preload the component:
~~~
'preload' => array( ..., 'jsonize'),
~~~

##API

~~~
function jsonizenc($data, $attributes=true, $onlySpecifiedRelations = false, $onlySpecifiedAttributes = false) {
	return json_encode(jsonize($data, $attributes, $onlySpecifiedRelations, $onlySpecifiedAttributes));
}
/**
 * 
 * @param mixed $data an ActiveRecord or array of ActiveRecord
 * @param $attributes array of attributes/relations to be processed e.g ['client','items' => ['product']] // nested relations
 * @param bool $onlySpecifiedRelations if to send all loaded relations or only the ones specified in $attributes 
 * @param bool $onlySpecifiedAttributes if to send all attributes or only the ones specified in $attributes 
 */
function jsonize($data, $attributes=true, $onlySpecifiedRelations = false, $onlySpecifiedAttributes = false);
~~~

##Usage

These example start from the simplest uses, progressively increasing the 
complexity of needed output.

~~~
Yii::app()->jsonize; // load the component (only needed if it isn't preloaded)

$user = new User::model()->findByPk(1);
$userArray = jsonize($user); // returns array('id'=>1,'name'=>'David')
echo json_encode($userArray); // output: {id: 1, name: 'David' }
echo jsonizenc($user); // shortcut for json_encode(jsonize($user))

$users = User::model()->findAll();
echo jsonizenc($users); // output: {{id: 1, name: 'David' }, {id: 2, name: 'John' }}

// now load relations
$user->posts; // load user's posts
echo jsonizenc($user);
// output is now: {
	"id":"1",
	"name":"David",
	"posts":[
		{
			"id":"1",
			"user_id":"1",
			"title":"new post"
		},{
			"id":"2",
			"user_id":"1",
			"title":"other post"
		}
	]
}

$user->comments; // load user's comments
echo jsonizenc($user);
// output is now: {
	"id":"1",
	"name":"David",
	"posts":[
		{
			"id":"1",
			"user_id":"1",
			"title":"new post"
		},{
			"id":"2",
			"user_id":"1",
			"title":"other post"
		}
	],
	"comments":[
		{
			"id":"1",
			"user_id":"1",
			"comment":"other post comment"
			"post_id": "2"
		}
	]
}
// encode an array of ActiveRecords
echo jsonizenc(User::model()->findAll()); // output [
	{
		id:1,
		name:'David'
	},{
		id:2,
		name:'John'
	}]
~~~

#Specifying attributes and relations

By default, all attributes (returned by getAttributes()) and all LOADED relations are processed

- Narrowing the relations to be processed
~~~
echo jsonizenc($user, array('posts'), true); 
// true means: only specified relations are to be processed (default: all loaded relations are processed)
~~~
- Narrowing the attributes
~~~
echo jsonizenc($user, array('name'), true, true); 
// true means: only process specified attributes (default: all attributes)
// output: { name: 'David' }

echo jsonizenc($user, array('name','posts'), true, true); // narrow relations and attributes
~~~
- Force loading relations

~~~
$user = User::model()->findByPk(1);

echo jsonizenc($user, array('posts')); 
// output is now: {
	"id":"1",
	"name":"David",
	"posts":[
		{
			"id":"1",
			"user_id":"1",
			"title":"new post"
		},{
			"id":"2",
			"user_id":"1",
			"title":"other post"
		}
	],
~~~
- Force loading nested relations
~~~
echo jsonizenc($users, array('posts'=>array('user'))); 
// output {
	"id":"1",
	"name":"David",
	"posts":[
		{
			"id":"1",
			"user_id":"1",
			"title":"new post",
			"user": {
				"id": 1,
				"name": "David"
			}
		},{
			"id":"2",
			"user_id":"1",
			"title":"other post",
			"user": {
				"id": 1,
				"name": "David"
			}
		}
	]
}
~~~
There's no limit to this notation, you could do:
~~~
echo jsonizenc($users, array('posts'=>array('user'=>array('posts','comments'))));
~~~
##Notes

For nested relations, the for specifying which attributes/relations are to be processed policy is the same as
the first one. For example:
~~~
echo jsonizenc($users, array('id','posts'=>array('title','user'=>array('name')), true, true)); 
// only specified attributes and relations of user, user.posts and user.posts.user will be processed
~~~

