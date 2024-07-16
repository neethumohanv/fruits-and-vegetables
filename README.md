# 🍎🥕 Fruits and Vegetables

## 🎯 Goal
We want to build a service which will take a `request.json` and:
* Process the file and create two separate collections for `Fruits` and `Vegetables`
* Each collection has methods like `add()`, `remove()`, `list()`;
* Units have to be stored as grams;
* Store the collections in a storage engine of your choice. (e.g. Database, In-memory)
* Provide an API endpoint to query the collections. As a bonus, this endpoint can accept filters to be applied to the returning collection.
* Provide another API endpoint to add new items to the collections (i.e., your storage engine).
* As a bonus you might:
  * consider giving option to decide which units are returned (kilograms/grams);
  * how to implement `search()` method collections;
  * use latest version of Symfony's to embbed your logic 
##  Results
* The application make use of Postgres SQL for storing the collections, and for testing its using in-memory database.
## To setup the application
* Run composer update within the project
* Perform migrations
* Serve the application
* Test cases can be found under the `tests` folder
## Assignment results
* ✅ Process the file and create two separate collections for `Fruits` and `Vegetables`
  * `http://localhost:8080/api/process-fooditems`
* ✅ Each collection has methods like `add()`, `remove()`, `list()`;
  * `add()` : `http://localhost:8080/api/fooditems/add`
  * `remove()` : `http://localhost:8080/api/fooditems/remove/{id}`
  * `list()` : `http://localhost:8080/api/fooditems`
* ✅ Units have to be stored as grams;
* ✅ Store the collections in a storage engine of your choice. (e.g. Database, In-memory)
* ✅ Provide an API endpoint to query the collections. As a bonus, this endpoint can accept filters to be applied to the returning collection.
  * `http://localhost:8080/api/fooditems/search/{type?}?name={}&quantity={}`
  * `http://localhost:8080/api/fooditems/search/fruit?name={}&quantity={=<300}`
  * `http://localhost:8080/api/fooditems/search/vegetable?name={}&quantity={=>300}`
* ✅ Provide another API endpoint to add new items to the collections (i.e., your storage engine).
  * `http://localhost:8080/api/fooditems/add/{type}`
* As a bonus you might:
  * ✅ consider giving option to decide which units are returned (kilograms/grams);
    * `http://localhost:8080/api/fooditems?unit={kg}`
  * ✅ how to implement `search()` method collections;
    * `http://localhost:8080/api/fooditems/search?name=apple`
  * ✅ use latest version of Symfony's to embbed your logic 



### ✔️ How can I check if my code is working?
You have two ways of moving on:
* You call the Service from PHPUnit test like it's done in dummy test (just run `bin/phpunit` from the console)

or

* You create a Controller which will be calling the service with a json payload

## 💡 Hints before you start working on it
* Keep KISS, DRY, YAGNI, SOLID principles in mind
* Timebox your work - we expect that you would spend between 3 and 4 hours.
* Your code should be tested

## When you are finished
* Please upload your code to a public git repository (i.e. GitHub, Gitlab)

## 🐳 Docker image
Optional. Just here if you want to run it isolated.

### 📥 Pulling image
```bash
docker pull tturkowski/fruits-and-vegetables
```

### 🧱 Building image
```bash
docker build -t tturkowski/fruits-and-vegetables -f docker/Dockerfile .
```

### 🏃‍♂️ Running container
```bash
docker run -it -w/app -v$(pwd):/app tturkowski/fruits-and-vegetables sh 
```

### 🛂 Running tests
```bash
docker run -it -w/app -v$(pwd):/app tturkowski/fruits-and-vegetables bin/phpunit
```

### ⌨️ Run development server
```bash
docker run -it -w/app -v$(pwd):/app -p8080:8080 tturkowski/fruits-and-vegetables php -S 0.0.0.0:8080 -t /app/public
# Open http://127.0.0.1:8080 in your browser
```
