# graphql-wp
A GraphQL endpoint for WordPress

Exposes a graph ql endpoint at */graphql* 


##Install
`composer require thefold/graphql-wp`

##Using

###wp_query
This is designed to follow WordPress' existing WP Query functions.  So as a rule you can pass the same parameters as your can to [WP Query](https://codex.wordpress.org/Class_Reference/WP_Query)*.

**In reality there are a lot of params you can pass to WP_Query, and I've only implemented the ones that I've needed so far. But adding more is trivial as the arguments are just passed directly to the get_posts function, so its just a matter of defining them in the schema.* 

    {"query":"{ 
    	wp_query { 
    		posts(paged: 1 posts_per_page: 10)  { 
    			title 
    			name 
    			terms (taxonomy:\"category\") { 
    				name 
    				slug 
    			}
    		}
    	}
    }"}

Will give you

    {
      "data": {
        "wp_query": {
          "posts": [
           {
              "title": "Much better than REST",
              "name": "so-easy-yes"
              "terms": [
              {
	              "name": "Example Category ",
	              "slug": "example-category"
	          }
              ]
           } ...

Also available on wp_query menu 

`{"query":"{ wp_query { menu(name: \"Main Menu\")  { title url} }}"}`

Will give you

    {
      "data": {
        "wp_query": {
          "menu": [
            {
              "title": "Home",
              "url": "http://graphqlwordpress.dev/"
            }
          ]
        }
      }
    }

###Post

And of course you can get an individual post *( but most of the time you'll probably use wp_query as your main entry point )*

`{"query":"{wp_post(ID:\"1\") { title, content, status }}"}`
