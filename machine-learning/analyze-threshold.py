import pandas as pd
import numpy as np
from sklearn.model_selection import train_test_split
from sklearn.preprocessing import StandardScaler
from tensorflow import keras
import pickle

# Load data
data = pd.read_csv('preprocessed_data/training-data.csv')
X = data[['next_week_state_of_the_economy']].values
y_direction = (data['direction'] == 'UP').astype(int).values

# Split data
X_train, X_test, y_train, y_test = train_test_split(
    X, y_direction, test_size=0.2, random_state=42, shuffle=False
)

# Scale
scaler = StandardScaler()
X_train_scaled = scaler.fit_transform(X_train)
X_test_scaled = scaler.transform(X_test)
X_test_lstm = X_test_scaled.reshape((X_test_scaled.shape[0], 1, 1))

# Load model
model = keras.models.load_model('xauusd_direction_model.keras')

# Get prediction probabilities (not binary)
y_pred_proba = model.predict(X_test_lstm).flatten()

print("="*60)
print("PREDICTION PROBABILITY ANALYSIS")
print("="*60)
print(f"\nPrediction probability stats:")
print(f"  Min: {y_pred_proba.min():.4f}")
print(f"  Max: {y_pred_proba.max():.4f}")
print(f"  Mean: {y_pred_proba.mean():.4f}")
print(f"  Median: {np.median(y_pred_proba):.4f}")
print(f"  Std: {y_pred_proba.std():.4f}")

# Test different thresholds
print("\n" + "="*60)
print("TESTING DIFFERENT THRESHOLDS")
print("="*60)
print(f"{'Threshold':<12} {'Accuracy':<12} {'UP%':<10} {'DOWN%':<10}")
print("-"*60)

best_accuracy = 0
best_threshold = 0.5

for threshold in np.arange(0.1, 0.9, 0.05):
    y_pred = (y_pred_proba > threshold).astype(int)
    accuracy = np.mean(y_pred == y_test)
    up_pct = np.sum(y_pred == 1) / len(y_pred) * 100
    down_pct = np.sum(y_pred == 0) / len(y_pred) * 100
    
    print(f"{threshold:.2f}         {accuracy*100:.2f}%        {up_pct:.1f}%      {down_pct:.1f}%")
    
    if accuracy > best_accuracy:
        best_accuracy = accuracy
        best_threshold = threshold

print("\n" + "="*60)
print("OPTIMAL THRESHOLD")
print("="*60)
print(f"Best threshold: {best_threshold:.2f}")
print(f"Best accuracy: {best_accuracy*100:.2f}%")

# Show prediction distribution at optimal threshold
y_pred_optimal = (y_pred_proba > best_threshold).astype(int)
print(f"\nAt threshold {best_threshold:.2f}:")
print(f"  Predicted UP: {np.sum(y_pred_optimal == 1)} ({np.sum(y_pred_optimal == 1)/len(y_pred_optimal)*100:.1f}%)")
print(f"  Predicted DOWN: {np.sum(y_pred_optimal == 0)} ({np.sum(y_pred_optimal == 0)/len(y_pred_optimal)*100:.1f}%)")
print(f"  Actual UP: {np.sum(y_test == 1)} ({np.sum(y_test == 1)/len(y_test)*100:.1f}%)")
print(f"  Actual DOWN: {np.sum(y_test == 0)} ({np.sum(y_test == 0)/len(y_test)*100:.1f}%)")

# Compare to default 0.5
y_pred_default = (y_pred_proba > 0.5).astype(int)
default_accuracy = np.mean(y_pred_default == y_test)
print(f"\nDefault (0.5) accuracy: {default_accuracy*100:.2f}%")
print(f"Improvement: {(best_accuracy - default_accuracy)*100:.2f} percentage points")
