# Delete old deployments
kubectl delete deployment scopa2-eu -n region-eu
kubectl delete deployment scopa2-us -n region-us

# Enter minikube env
eval $(minikube docker-env)

# Build image
docker build -t scopa2:latest .

# Deploy
helm upgrade --install scopa2-eu ./helm \
  -f helm/values.yaml \
  -f helm/values-eu.yaml \
  -n region-eu --create-namespace
helm upgrade --install scopa2-us ./helm \
  -f helm/values.yaml \
  -f helm/values-us.yaml \
  -n region-us --create-namespace

# Run migrations
kubectl exec -n region-eu -it $(kubectl get pods -n region-eu -l app=scopa2-eu -o jsonpath="{.items[0].metadata.name}") -- php artisan migrate --force
