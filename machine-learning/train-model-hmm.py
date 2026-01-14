import pandas as pd
import numpy as np
from sklearn.model_selection import train_test_split
from sklearn.preprocessing import StandardScaler
from sklearn.metrics import classification_report, accuracy_score, mean_absolute_error, r2_score
from hmmlearn import hmm
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
print("TRAINING DIRECTION CLASSIFIER (Hidden Markov Model)")
print("="*60)

# Create sequences for HMM
# We'll use economic states as observations and predict direction
print("\nPreparing sequences for HMM...")

# Discretize economic states into bins for HMM (HMM works with discrete observations)
n_bins = 10
X_train_discrete = np.zeros_like(X_train_scaled, dtype=int)
X_test_discrete = np.zeros_like(X_test_scaled, dtype=int)

for i in range(X_train_scaled.shape[1]):
    # Create bins based on training data
    bins = np.percentile(X_train_scaled[:, i], np.linspace(0, 100, n_bins+1))
    X_train_discrete[:, i] = np.digitize(X_train_scaled[:, i], bins[1:-1])
    X_test_discrete[:, i] = np.digitize(X_test_scaled[:, i], bins[1:-1])

# Combine features into single observation state
X_train_obs = X_train_discrete[:, 0] * 100 + X_train_discrete[:, 1] * 10 + X_train_discrete[:, 2]
X_test_obs = X_test_discrete[:, 0] * 100 + X_test_discrete[:, 1] * 10 + X_test_discrete[:, 2]

# Train separate HMMs for UP and DOWN states
print("\nTraining HMM for UP direction...")
X_train_up = X_train_obs[y_dir_train == 1].reshape(-1, 1)
hmm_up = hmm.GaussianHMM(n_components=3, covariance_type="diag", n_iter=100, random_state=42)
hmm_up.fit(X_train_up)

print("Training HMM for DOWN direction...")
X_train_down = X_train_obs[y_dir_train == 0].reshape(-1, 1)
hmm_down = hmm.GaussianHMM(n_components=3, covariance_type="diag", n_iter=100, random_state=42)
hmm_down.fit(X_train_down)

# Predict on test set
print("\nPredicting directions...")
y_dir_pred = []

for obs in X_test_obs:
    obs_reshaped = np.array([[obs]])
    
    # Calculate log likelihood for each model
    try:
        score_up = hmm_up.score(obs_reshaped)
    except:
        score_up = -np.inf
    
    try:
        score_down = hmm_down.score(obs_reshaped)
    except:
        score_down = -np.inf
    
    # Predict based on which model gives higher likelihood
    if score_up > score_down:
        y_dir_pred.append(1)  # UP
    else:
        y_dir_pred.append(0)  # DOWN

y_dir_pred = np.array(y_dir_pred)

# Evaluate direction model
print("\n--- Direction Prediction Results ---")
print(f"Accuracy: {accuracy_score(y_dir_test, y_dir_pred)*100:.2f}%")
print("\nClassification Report:")
print(classification_report(y_dir_test, y_dir_pred, target_names=['DOWN', 'UP']))

print("\n" + "="*60)
print("TRAINING MAGNITUDE REGRESSOR (Markov Chain)")
print("="*60)

# For magnitude, use Markov Chain approach based on state transitions
print("\nBuilding Markov Chain for magnitude prediction...")

# Discretize magnitudes into states
mag_bins = np.percentile(y_mag_train, [0, 25, 50, 75, 100])
y_mag_train_states = np.digitize(y_mag_train, mag_bins[1:-1])
y_mag_test_states = np.digitize(y_mag_test, mag_bins[1:-1])

# Build transition matrix
n_mag_states = len(np.unique(y_mag_train_states))
transition_matrix = np.zeros((n_mag_states, n_mag_states))

for i in range(len(y_mag_train_states) - 1):
    current_state = y_mag_train_states[i]
    next_state = y_mag_train_states[i + 1]
    transition_matrix[current_state, next_state] += 1

# Normalize rows
row_sums = transition_matrix.sum(axis=1, keepdims=True)
row_sums[row_sums == 0] = 1  # Avoid division by zero
transition_matrix = transition_matrix / row_sums

print(f"Transition Matrix ({n_mag_states} states):")
print(transition_matrix)

# Calculate average magnitude for each state
state_magnitudes = np.zeros(n_mag_states)
for state in range(n_mag_states):
    mask = y_mag_train_states == state
    if mask.any():
        state_magnitudes[state] = y_mag_train[mask].mean()

print(f"\nAverage magnitude per state: {state_magnitudes}")

# Predict magnitudes using Markov Chain
y_mag_pred = []
current_state = y_mag_train_states[-1]  # Start from last training state

for i in range(len(y_mag_test)):
    # Get next state based on transition probabilities
    next_state_probs = transition_matrix[current_state]
    if next_state_probs.sum() > 0:
        next_state = np.argmax(next_state_probs)
    else:
        next_state = current_state
    
    # Predict magnitude for next state
    predicted_mag = state_magnitudes[next_state]
    y_mag_pred.append(predicted_mag)
    
    # Update current state (use actual for next prediction in sequence)
    current_state = y_mag_test_states[i]

y_mag_pred = np.array(y_mag_pred)

# Evaluate magnitude model
print("\n--- Magnitude Prediction Results ---")
mae = mean_absolute_error(y_mag_test, y_mag_pred)
r2 = r2_score(y_mag_test, y_mag_pred)
print(f"Mean Absolute Error: {mae:.4f}%")
print(f"R² Score: {r2:.4f}")

# Save models
print("\n" + "="*60)
print("SAVING MODELS")
print("="*60)

model_data = {
    'scaler': scaler,
    'hmm_up': hmm_up,
    'hmm_down': hmm_down,
    'transition_matrix': transition_matrix,
    'state_magnitudes': state_magnitudes,
    'mag_bins': mag_bins,
    'n_bins': n_bins,
    'train_bins': [np.percentile(X_train_scaled[:, i], np.linspace(0, 100, n_bins+1)) for i in range(3)]
}

with open('hmm_model.pkl', 'wb') as f:
    pickle.dump(model_data, f)

print("\nModels saved to: hmm_model.pkl")

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
print("MARKOV MODEL COMPARISON")
print("="*60)

print("\nMarkov models work by:")
print("1. HMM: Models hidden states (market conditions) that generate observations")
print("2. Markov Chain: Uses transition probabilities between states")
print("3. Assumes Markov property: Future depends only on present state")

print("\nAdvantages:")
print("- Captures state transitions in market behavior")
print("- Probabilistic framework handles uncertainty")
print("- Simple and interpretable")

print("\nLimitations:")
print("- Assumes states are independent (Markov property)")
print("- May oversimplify complex market dynamics")
print("- LSTM better at capturing long-term dependencies")

print("\n" + "="*60)
print("TRAINING COMPLETE!")
print("="*60)
