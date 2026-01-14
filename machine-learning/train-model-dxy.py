import pandas as pd
import numpy as np
from sklearn.model_selection import train_test_split
from sklearn.ensemble import RandomForestClassifier, RandomForestRegressor
from sklearn.preprocessing import StandardScaler
from sklearn.metrics import classification_report, accuracy_score, mean_absolute_error, r2_score
import pickle

print("Loading DXY training data...")
df = pd.read_csv('preprocessed_data/training-data-dxy.csv')

print(f"Total records: {len(df)}")
print(f"\nFirst few rows:")
print(df.head())

# Prepare features (X) - economic states
X = df[['today_state_of_the_economy', 'tomorrow_state_of_the_economy', 'next_week_state_of_the_economy']].values

# Prepare targets (y)
# For direction: Convert UP/DOWN to binary (1 for UP, 0 for DOWN)
df['direction_binary'] = df['direction'].apply(lambda x: 1 if x == 'UP' else 0)
y_direction = df['direction_binary'].values

# For magnitude: Use the actual magnitude value
y_magnitude = df['magnitude'].values

print(f"\nFeatures shape: {X.shape}")
print(f"Direction labels shape: {y_direction.shape}")
print(f"Magnitude labels shape: {y_magnitude.shape}")

# Check class distribution
print(f"\nDirection distribution:")
print(f"UP: {np.sum(y_direction == 1)} ({np.sum(y_direction == 1)/len(y_direction)*100:.2f}%)")
print(f"DOWN: {np.sum(y_direction == 0)} ({np.sum(y_direction == 0)/len(y_direction)*100:.2f}%)")

# Split data into train and test sets (80/20 split)
X_train, X_test, y_dir_train, y_dir_test, y_mag_train, y_mag_test = train_test_split(
    X, y_direction, y_magnitude, test_size=0.2, random_state=42, shuffle=False
)

print(f"\nTraining set size: {len(X_train)}")
print(f"Test set size: {len(X_test)}")

# Scale features
scaler = StandardScaler()
X_train_scaled = scaler.fit_transform(X_train)
X_test_scaled = scaler.transform(X_test)

print("\n" + "="*60)
print("TRAINING DIRECTION CLASSIFIER (Random Forest)")
print("="*60)

# Train Random Forest for direction
gb_classifier = RandomForestClassifier(
    n_estimators=200,
    max_depth=10,
    min_samples_split=20,
    min_samples_leaf=10,
    random_state=42,
    n_jobs=-1,
    verbose=0
)

print("\nTraining model...")
gb_classifier.fit(X_train_scaled, y_dir_train)

# Predict on test set
y_dir_pred = gb_classifier.predict(X_test_scaled)

# Evaluate direction model
print("\n--- Direction Prediction Results ---")
print(f"Accuracy: {accuracy_score(y_dir_test, y_dir_pred)*100:.2f}%")
print("\nClassification Report:")
print(classification_report(y_dir_test, y_dir_pred, target_names=['DOWN', 'UP']))

# Feature importance
feature_names = ['Today State', 'Tomorrow State', 'Next Week State']
importances = gb_classifier.feature_importances_
print("\nFeature Importance:")
for name, importance in zip(feature_names, importances):
    print(f"  {name}: {importance*100:.2f}%")

print("\n" + "="*60)
print("TRAINING MAGNITUDE REGRESSOR (Random Forest)")
print("="*60)

# Train Random Forest for magnitude
gb_regressor = RandomForestRegressor(
    n_estimators=200,
    max_depth=10,
    min_samples_split=20,
    min_samples_leaf=10,
    random_state=42,
    n_jobs=-1,
    verbose=0
)

print("\nTraining model...")
gb_regressor.fit(X_train_scaled, y_mag_train)

# Predict on test set
y_mag_pred = gb_regressor.predict(X_test_scaled)

# Evaluate magnitude model
print("\n--- Magnitude Prediction Results ---")
mae = mean_absolute_error(y_mag_test, y_mag_pred)
r2 = r2_score(y_mag_test, y_mag_pred)
print(f"Mean Absolute Error: {mae:.4f}%")
print(f"R² Score: {r2:.4f}")

# Feature importance
importances = gb_regressor.feature_importances_
print("\nFeature Importance:")
for name, importance in zip(feature_names, importances):
    print(f"  {name}: {importance*100:.2f}%")

# Save models
print("\n" + "="*60)
print("SAVING MODELS")
print("="*60)

model_data = {
    'scaler': scaler,
    'direction_model': gb_classifier,
    'magnitude_model': gb_regressor,
    'feature_names': feature_names
}

with open('trained_model_dxy.pkl', 'wb') as f:
    pickle.dump(model_data, f)

print("\nModels saved to: trained_model_dxy.pkl")

# Show some sample predictions
print("\n" + "="*60)
print("SAMPLE PREDICTIONS (First 10 from test set)")
print("="*60)
print("\n{:<6} {:<8} {:<8} {:<12} {:<12}".format("Index", "Actual", "Predicted", "Actual Mag", "Pred Mag"))
print("-" * 60)

for i in range(min(10, len(y_dir_test))):
    actual_dir = 'UP' if y_dir_test[i] == 1 else 'DOWN'
    pred_dir = 'UP' if y_dir_pred[i] == 1 else 'DOWN'
    actual_mag = y_mag_test[i]
    pred_mag = y_mag_pred[i]
    
    print("{:<6} {:<8} {:<8} {:<12.2f} {:<12.2f}".format(
        i, actual_dir, pred_dir, actual_mag, pred_mag
    ))

print("\n" + "="*60)
print("TRAINING COMPLETE!")
print("="*60)
