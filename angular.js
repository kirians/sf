var todoController = angular.module('todoController', []);

todoController.controller('TodoCtrl', function ($scope, $rootScope, $state, $filter, Todo, Task) {
    $rootScope.loader = true;
    $rootScope.currentRoute = $state.current.name;
    $rootScope.activeMenuItem = 'todo';
    
    $scope.todo = [];
    $scope.todoTasks = [];
    $scope.todoDealings = [];
    
    Todo.list.query(function(response) {
        if(response.status) {
            $scope.todo = response.todo;
            $scope.todoTasks = response.tasks;
            $scope.todoDealings = response.dealings;
        }
        
        $rootScope.loader = false;
    });
    
    $scope.todoCompleted = function(key) {
        var getKey = $filter('getItemById')($scope.todo, key);
        
        if($scope.todo[getKey.index]) {
            Todo.completed.query({ id: $scope.todo[getKey.index].id }, function (responce) {
                if(responce && responce.status) {
                    $scope.todo[getKey.index].isCompleted = responce.completed;
                }
            });
        }
    };
    
    $scope.addTodo = function() {
        if($scope.todoText) {
            Todo.add.query({ todo: $scope.todoText }, function(response) {
                if(response && response.status) {
                    $scope.todo.unshift(response.todo);
                    
                    $scope.todoText = null;
                    $scope.todoForm = false;
                }
            });
        }
    };
    
    $scope.removeTodo = function(key) {
        var getKey = $filter('getItemById')($scope.todo, key);
        
        if($scope.todo[getKey.index]) {
            Todo.remove.query({ id: $scope.todo[getKey.index].id }, function (responce) {
                if(responce && responce.status) {
                    $scope.todo.splice(getKey.index, 1);
                }
            });
        }
    };
    
    $scope.taskAttached = function(key) {
        if($scope.todoTasks[key]) {
            var task = $scope.todoTasks[key];
            
            var data = {
                id: task.id,
                projectId: task.project.id
            };

            Task.attached.query(data, function (responce) {
                if (responce) {
                    $scope.todoTasks[key].attached = responce.attached ? true: false;
                }
            });
        }
    };
    
    $scope.completedTask = function(key) {
        if($scope.todoTasks[key]) {
            var data = {
                id: $scope.todoTasks[key].id,
                project_id: $scope.todoTasks[key].project.id
            };

            Task.completed.query(data, function (response) {
                if(response.status == 'success') {
                    $scope.todoTasks.splice(key, 1);
                }
            });
        };
    };
});

todoController.filter('getItemById', function() {
    return function(input, id) {
        var i = 0, len = input.length;
        
        for(; i < len; i++) {
            if(+input[i].id == +id) {
                return {
                    item: input[i],
                    index: i
                };
            }
        }
        return null;
    };
});