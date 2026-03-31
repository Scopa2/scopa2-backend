kubectl exec -n region-eu -it $(kubectl get pods -n region-eu -l app=scopa2-eu -o jsonpath="{.items[0].metadata.name}") -- bash
